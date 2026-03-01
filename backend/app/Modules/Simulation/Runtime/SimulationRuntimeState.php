<?php

namespace App\Modules\Simulation\Runtime;

use App\Modules\Simulation\Enums\CallStatusEnum;
use App\Modules\Simulation\Enums\DispatchAlgorithmEnum;
use App\Modules\Simulation\Enums\DoorStateEnum;
use App\Modules\Simulation\Enums\ElevatorConditionEnum;
use App\Modules\Simulation\Enums\ElevatorDirectionEnum;
use App\Modules\Simulation\Enums\ElevatorStateEnum;
use App\Modules\Simulation\Enums\HallCallDirectionEnum;
use App\Modules\Simulation\Enums\SimulationModeEnum;

final class SimulationRuntimeState
{
    /**
     * @param list<ElevatorRuntimeState> $elevators
     * @param list<HallCallState> $pendingHallCalls
     */
    public function __construct(
        public string $simulationId,
        public int $tickNumber,
        public int $floors,
        public int $maxPendingCalls,
        public int $emergencyDescentMultiplier,
        public int $doorHoldTicks,
        public int $pickedUpPassengers,
        public int $droppedOffPassengers,
        public SimulationModeEnum $mode,
        public DispatchAlgorithmEnum $algorithm,
        public bool $isEmergencyMode,
        public array $elevators,
        public array $pendingHallCalls,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'simulationId'               => $this->simulationId,
            'tickNumber'                 => $this->tickNumber,
            'floors'                     => $this->floors,
            'maxPendingCalls'            => $this->maxPendingCalls,
            'emergencyDescentMultiplier' => $this->emergencyDescentMultiplier,
            'doorHoldTicks'              => $this->doorHoldTicks,
            'pickedUpPassengers'         => $this->pickedUpPassengers,
            'droppedOffPassengers'       => $this->droppedOffPassengers,
            'mode'                       => $this->mode->value,
            'algorithm'                  => $this->algorithm->value,
            'isEmergencyMode'            => $this->isEmergencyMode,
            'elevators'                  => array_map(
                static fn (ElevatorRuntimeState $e): array => [
                    'elevatorId'          => $e->elevatorId,
                    'shaftNumber'         => $e->shaftNumber,
                    'currentFloor'        => $e->currentFloor,
                    'currentLoad'         => $e->currentLoad,
                    'capacity'            => $e->capacity,
                    'pickedUpPassengers'  => $e->pickedUpPassengers,
                    'droppedOffPassengers' => $e->droppedOffPassengers,
                    'overloadSavedLoad'   => $e->overloadSavedLoad,
                    'direction'           => $e->direction->value,
                    'state'               => $e->state->value,
                    'condition'           => $e->condition->value,
                    'doorState'           => $e->doorState->value,
                    'doorTimerTicks'      => $e->doorTimerTicks,
                    'plannedStops'        => $e->plannedStops,
                ],
                $this->elevators,
            ),
            'pendingHallCalls'           => array_map(
                static fn (HallCallState $call): array => [
                    'callId'             => $call->callId,
                    'originFloor'        => $call->originFloor,
                    'targetFloor'        => $call->targetFloor,
                    'direction'          => $call->direction->value,
                    'passengerWeight'    => $call->passengerWeight,
                    'ageTicks'           => $call->ageTicks,
                    'status'             => $call->status->value,
                    'assignedElevatorId' => $call->assignedElevatorId,
                ],
                $this->pendingHallCalls,
            ),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        /** @var array<string, int|string> $defaults */
        $defaults = config('simulation.defaults', []);

        $elevators = array_map(
            static function (array $e): ElevatorRuntimeState {
                $elevatorId  = (string) $e['elevatorId'];
                $shaftNumber = isset($e['shaftNumber']) ? (int) $e['shaftNumber'] : 0;

                if (!isset($e['shaftNumber']) && preg_match('/^E(\d+)$/', $elevatorId, $matches) === 1) {
                    $shaftNumber = max(0, (int) $matches[1] - 1);
                }

                $state             = ElevatorStateEnum::from((string) ($e['state'] ?? 'Idle'));
                $condition         = ElevatorConditionEnum::from((string) ($e['condition'] ?? 'Normal'));
                $overloadSavedLoad = isset($e['overloadSavedLoad']) ? (int) $e['overloadSavedLoad'] : null;

                return new ElevatorRuntimeState(
                    elevatorId:           $elevatorId,
                    shaftNumber:          $shaftNumber,
                    currentFloor:         (int) $e['currentFloor'],
                    currentLoad:          (int) $e['currentLoad'],
                    capacity:             (int) $e['capacity'],
                    pickedUpPassengers:   (int) ($e['pickedUpPassengers'] ?? 0),
                    droppedOffPassengers: (int) ($e['droppedOffPassengers'] ?? 0),
                    overloadSavedLoad:    $overloadSavedLoad,
                    direction:            ElevatorDirectionEnum::from((string) $e['direction']),
                    state:                $state,
                    condition:            $condition,
                    doorState:            DoorStateEnum::from((string) $e['doorState']),
                    doorTimerTicks:       (int) ($e['doorTimerTicks'] ?? 0),
                    plannedStops:         array_map('intval', $e['plannedStops'] ?? []),
                );
            },
            $payload['elevators'] ?? [],
        );

        $pendingHallCalls = array_map(
            static fn (array $call): HallCallState => new HallCallState(
                callId:             (string) $call['callId'],
                originFloor:        (int) $call['originFloor'],
                targetFloor:        (int) $call['targetFloor'],
                direction:          HallCallDirectionEnum::from((string) $call['direction']),
                passengerWeight:    (int) $call['passengerWeight'],
                ageTicks:           (int) $call['ageTicks'],
                status:             CallStatusEnum::from((string) $call['status']),
                assignedElevatorId: isset($call['assignedElevatorId']) ? (string) $call['assignedElevatorId'] : null,
            ),
            $payload['pendingHallCalls'] ?? [],
        );

        return new self(
            simulationId:               (string) ($payload['simulationId'] ?? ''),
            tickNumber:                 (int) ($payload['tickNumber'] ?? 0),
            floors:                     (int) ($payload['floors'] ?? $defaults['floors']),
            maxPendingCalls:            (int) ($payload['maxPendingCalls'] ?? $defaults['maxPendingCalls']),
            emergencyDescentMultiplier: (int) ($payload['emergencyDescentMultiplier'] ?? $defaults['emergencyDescentMultiplier']),
            doorHoldTicks:              (int) ($payload['doorHoldTicks'] ?? max(1, (int) round((int) $defaults['doorOpenSeconds'] * 1000 / max(1, (int) $defaults['tickIntervalMs'])))),
            pickedUpPassengers:         (int) ($payload['pickedUpPassengers'] ?? 0),
            droppedOffPassengers:       (int) ($payload['droppedOffPassengers'] ?? 0),
            mode:                       SimulationModeEnum::from((string) ($payload['mode'] ?? $defaults['mode'])),
            algorithm:                  DispatchAlgorithmEnum::from((string) ($payload['algorithm'] ?? $defaults['algorithm'])),
            isEmergencyMode:            (bool) ($payload['isEmergencyMode'] ?? false),
            elevators:                  $elevators,
            pendingHallCalls:           $pendingHallCalls,
        );
    }
}
