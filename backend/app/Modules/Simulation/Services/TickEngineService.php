<?php

namespace App\Modules\Simulation\Services;

use App\Modules\Simulation\Calls\DispatchService;
use App\Modules\Simulation\Calls\ModeTrafficService;
use App\Modules\Simulation\DTOs\SimulationSnapshotDto;
use App\Modules\Simulation\Emergency\EmergencyService;
use App\Modules\Simulation\Enums\CallStatusEnum;
use App\Modules\Simulation\Enums\DoorStateEnum;
use App\Modules\Simulation\OutOfService\OutOfServiceService;
use App\Modules\Simulation\Overload\OverloadService;
use App\Modules\Simulation\Physics\DoorService;
use App\Modules\Simulation\Physics\MovementService;
use App\Modules\Simulation\Repositories\RedisRuntimeStateRepository;
use App\Modules\Simulation\Runtime\ElevatorRuntimeState;
use App\Modules\Simulation\Runtime\SimulationRuntimeState;
use Random\RandomException;

final readonly class TickEngineService
{
    public function __construct(
        private RedisRuntimeStateRepository $runtimeStateRepository,
        private ModeTrafficService $modeTrafficService,
        private DispatchService $dispatchService,
        private OverloadService $overloadService,
        private EmergencyService $emergencyService,
        private OutOfServiceService $outOfServiceService,
        private MovementService $movementService,
        private DoorService $doorService,
        private SimulationStateMutexService $simulationStateMutexService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function runSingleTick(string $simulationId): array
    {
        return $this->simulationStateMutexService->withLock($simulationId, function () use ($simulationId): array {
            $state = $this->runtimeStateRepository->loadState($simulationId);
            $state->tickNumber++;

            if ($state->isEmergencyMode) {
                $state->pendingHallCalls = [];
            } else {
                $this->incrementPendingCallAge($state);
                $this->generateTrafficIfNeeded($state);
                $this->dispatchService->assignPendingCalls($state);
            }

            $arrivals = $this->stepElevators($state);
            if (!$state->isEmergencyMode) {
                $this->dispatchService->assignPendingCalls($state);
            }

            $payload = SimulationSnapshotDto::fromState($state)->toArray();
            $payload['arrivals'] = $arrivals;

            $this->runtimeStateRepository->saveState($state);
            $this->runtimeStateRepository->publishTick($simulationId, $payload);

            return $payload;
        });
    }

    private function incrementPendingCallAge(SimulationRuntimeState $state): void
    {
        foreach ($state->pendingHallCalls as $call) {
            if ($call->status === CallStatusEnum::Pending) {
                $call->ageTicks++;
            }
        }
    }

    /**
     * @throws RandomException
     */
    private function generateTrafficIfNeeded(SimulationRuntimeState $state): void
    {
        $activeCalls = 0;
        foreach ($state->pendingHallCalls as $call) {
            if ($call->status === CallStatusEnum::Pending || $call->status === CallStatusEnum::Assigned) {
                $activeCalls++;
            }
        }

        if ($activeCalls >= $state->maxPendingCalls) {
            return;
        }

        $newCall = $this->modeTrafficService->maybeGenerateCall($state);

        if ($newCall === null) {
            return;
        }

        $state->pendingHallCalls[] = $newCall;
    }

    /**
     * @return list<array<string, int|string>>
     */
    private function stepElevators(SimulationRuntimeState $state): array
    {
        if ($state->isEmergencyMode) {
            return $this->emergencyService->stepRecallTick($state);
        }

        $arrivals = [];
        $this->overloadService->evaluate($state);
        $this->outOfServiceService->progressTransitions($state);
        $this->overloadService->releaseAssignedCallsForAutoOverloadedElevators($state);
        $this->rebuildElevatorStopsFromCalls($state);

        foreach ($state->elevators as $elevator) {
            $this->doorService->progress($elevator, $state->doorHoldTicks);
            $this->boardAssignedCallsAtCurrentOpenFloor($state, $elevator);
            $arrivedFloor = $this->movementService->step($elevator);

            if ($arrivedFloor === null) {
                continue;
            }

            $arrivals[] = [
                'elevatorId' => $elevator->elevatorId,
                'floor' => $arrivedFloor,
            ];

            $this->handleStopAtFloor($state, $elevator, $arrivedFloor);
        }

        // Finalize pending out-of-service transitions in the same tick
        // after movement/drop-off so UI does not remain in "pending" while stopped
        $this->outOfServiceService->progressTransitions($state);

        $this->pruneResolvedCalls($state);

        return $arrivals;
    }

    private function rebuildElevatorStopsFromCalls(SimulationRuntimeState $state): void
    {
        foreach ($state->elevators as $elevator) {
            if ($elevator->isUnavailableForDispatch() || $state->isEmergencyMode) {
                continue;
            }

            $elevator->plannedStops = [];

            foreach ($state->pendingHallCalls as $call) {
                if ($call->assignedElevatorId !== $elevator->elevatorId) {
                    continue;
                }

                if ($call->status === CallStatusEnum::Assigned) {
                    $elevator->appendStop($call->originFloor);
                    continue;
                }

                if ($call->status === CallStatusEnum::Riding) {
                    $elevator->appendStop($call->targetFloor);
                }
            }
        }
    }

    private function handleStopAtFloor(SimulationRuntimeState $state, ElevatorRuntimeState $elevator, int $floor): void
    {
        foreach ($state->pendingHallCalls as $call) {
            if ($call->assignedElevatorId !== $elevator->elevatorId) {
                continue;
            }

            if ($call->status === CallStatusEnum::Riding && $call->targetFloor === $floor) {
                $call->status = CallStatusEnum::Served;
                $elevator->alight($call->passengerWeight);
                $state->droppedOffPassengers++;
            }
        }

        $this->boardAssignedCallsAtCurrentOpenFloor($state, $elevator);
    }

    private function boardAssignedCallsAtCurrentOpenFloor(
        SimulationRuntimeState $state,
        ElevatorRuntimeState $elevator,
    ): void {
        if ($elevator->doorState !== DoorStateEnum::Open) {
            return;
        }

        $floor = $elevator->currentFloor;

        foreach ($state->pendingHallCalls as $call) {
            if ($call->assignedElevatorId !== $elevator->elevatorId) {
                continue;
            }

            if ($call->status !== CallStatusEnum::Assigned || $call->originFloor !== $floor) {
                continue;
            }

            if (($elevator->currentLoad + $call->passengerWeight) > $elevator->capacity && $elevator->overloadSavedLoad === null) {
                $call->status = CallStatusEnum::Pending;
                $call->assignedElevatorId = null;
                continue;
            }

            $call->status = CallStatusEnum::Riding;
            $elevator->board($call->passengerWeight);
            $state->pickedUpPassengers++;
            $elevator->appendStop($call->targetFloor);
        }
    }

    private function pruneResolvedCalls(SimulationRuntimeState $state): void
    {
        $state->pendingHallCalls = array_values(
            array_filter(
                $state->pendingHallCalls,
                static fn ($call): bool => $call->status !== CallStatusEnum::Served,
            ),
        );
    }

}
