<?php

namespace App\Modules\Simulation\Calls;

use App\Modules\Simulation\Algorithms\DispatchStrategyFactory;
use App\Modules\Simulation\Algorithms\DispatchStrategyInterface;
use App\Modules\Simulation\DTOs\DispatchCandidate;
use App\Modules\Simulation\DTOs\ManualCallDto;
use App\Modules\Simulation\Enums\CallStatusEnum;
use App\Modules\Simulation\Enums\DoorStateEnum;
use App\Modules\Simulation\Enums\HallCallDirectionEnum;
use App\Modules\Simulation\Runtime\ElevatorRuntimeState;
use App\Modules\Simulation\Runtime\HallCallState;
use App\Modules\Simulation\Runtime\SimulationRuntimeState;
use Random\RandomException;

final readonly class DispatchService
{
    public function __construct(
        private DispatchStrategyFactory $dispatchStrategyFactory
    ) {
    }

    /**
     * Append one manual hall call and try to assign it immediately
     *
     * @return array<string, int|string>|null
     * @throws RandomException
     */
    public function assignManualCall(SimulationRuntimeState $state, ManualCallDto $call): ?array
    {
        $callId    = HallCallState::newId($state->simulationId, $state->tickNumber, 'manual');
        $direction = HallCallDirectionEnum::fromFloors($call->originFloor, $call->destinationFloor);

        $state->pendingHallCalls[] = new HallCallState(
            callId          : $callId,
            originFloor     : $call->originFloor,
            targetFloor     : $call->destinationFloor,
            direction       : $direction,
            passengerWeight : $call->passengerWeight,
        );

        return $this->assignPendingCalls($state)[0] ?? null;
    }

    /**
     * Dispatch all currently pending calls using same-floor fast path, strategy and fallback
     *
     * @return list<array<string, int|string>>
     */
    public function assignPendingCalls(SimulationRuntimeState $state): array
    {
        $strategy    = $this->dispatchStrategyFactory->make($state->algorithm);
        $assignments = [];

        foreach ($state->pendingHallCalls as $call) {
            if ($call->status !== CallStatusEnum::Pending) {
                continue;
            }

            $sameFloorElevatorId = $this->findSameFloorElevator($state, $call->originFloor, $call->passengerWeight);
            if ($sameFloorElevatorId !== null) {
                $assignment = $this->assignCallToElevator($state, $call, $sameFloorElevatorId);

                if ($assignment !== null) {
                    $assignments[] = $assignment;
                    continue;
                }
            }

            $manualCall = new ManualCallDto(
                originFloor      : $call->originFloor,
                destinationFloor : $call->targetFloor,
                passengerWeight  : $call->passengerWeight,
            );

            $candidate = $this->selectCandidateForCall($state, $strategy, $manualCall, null);
            if ($candidate === null) {
                continue;
            }

            $assignment = $this->assignCallToElevator($state, $call, $candidate->elevatorId);
            if ($assignment === null) {
                $fallback = $this->selectCandidateForCall($state, $strategy, $manualCall, $candidate->elevatorId);
                if ($fallback !== null) {
                    $assignment = $this->assignCallToElevator($state, $call, $fallback->elevatorId);
                }
            }

            if ($assignment !== null) {
                $assignments[] = $assignment;
            }
        }

        return $assignments;
    }

    /**
     * Assign call to a concrete elevator and enqueue pickup/destination stops
     */
    private function assignCallToElevator(SimulationRuntimeState $state, HallCallState $call, string $elevatorId): ?array
    {
        foreach ($state->elevators as $elevator) {
            if ($elevator->elevatorId !== $elevatorId) {
                continue;
            }

            if (!$this->canAcceptCall($state, $elevator->elevatorId, $call->passengerWeight)) {
                return null;
            }

            $call->status             = CallStatusEnum::Assigned;
            $call->assignedElevatorId = $elevator->elevatorId;

            if ($elevator->currentFloor === $call->originFloor) {
                $this->promoteToRidingImmediately($state, $elevator, $call);
            } else {
                $elevator->appendStop($call->originFloor);
            }


            return [
                'callId'     => $call->callId,
                'elevatorId' => $elevator->elevatorId,
            ];
        }

        return null;
    }

    /**
     * Immediate pickup path when elevator is already at the origin floor
     */
    private function promoteToRidingImmediately(
        SimulationRuntimeState $state,
        ElevatorRuntimeState $elevator,
        HallCallState $call,
    ): void {
        $call->status = CallStatusEnum::Riding;
        $elevator->board($call->passengerWeight);
        $state->pickedUpPassengers++;
        $elevator->appendStop($call->targetFloor);

        if ($elevator->doorState === DoorStateEnum::Closed || $elevator->doorState === DoorStateEnum::Closing) {
            $elevator->startOpening();
        }
    }

    /**
     * Fast-path lookup for an available elevator already on the caller floor
     */
    private function findSameFloorElevator(SimulationRuntimeState $state, int $originFloor, int $passengerWeight): ?string
    {
        foreach ($state->elevators as $elevator) {
            if ($elevator->isUnavailableForDispatch()) {
                continue;
            }

            if ($elevator->currentFloor !== $originFloor) {
                continue;
            }

            if (!$this->canAcceptCall($state, $elevator->elevatorId, $passengerWeight)) {
                continue;
            }

            return $elevator->elevatorId;
        }

        return null;
    }

    /**
     * Capacity guard that includes reserved load from assigned-but-not-boarded calls
     */
    private function canAcceptCall(SimulationRuntimeState $state, string $elevatorId, int $passengerWeight): bool
    {
        foreach ($state->elevators as $elevator) {
            if ($elevator->elevatorId !== $elevatorId) {
                continue;
            }

            $reservedLoad = 0;
            foreach ($state->pendingHallCalls as $call) {
                if ($call->assignedElevatorId === $elevatorId && $call->status === CallStatusEnum::Assigned) {
                    $reservedLoad += $call->passengerWeight;
                }
            }

            return ($elevator->currentLoad + $reservedLoad + $passengerWeight) <= $elevator->capacity;
        }

        return false;
    }

    /**
     * Ask active strategy for best candidate, optionally excluding one elevator
     */
    private function selectCandidateForCall(
        SimulationRuntimeState $state,
        DispatchStrategyInterface $strategy,
        ManualCallDto $manualCall,
        ?string $excludedElevatorId,
    ): ?DispatchCandidate {
        $elevators = $state->elevators;

        if ($excludedElevatorId !== null) {
            $elevators = array_values(
                array_filter(
                    $elevators,
                    static fn (ElevatorRuntimeState $elevator): bool => $elevator->elevatorId !== $excludedElevatorId,
                ),
            );
        }

        if ($elevators === []) {
            return null;
        }

        return $strategy->selectCar($elevators, $manualCall);
    }
}
