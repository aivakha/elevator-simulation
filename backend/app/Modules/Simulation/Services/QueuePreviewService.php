<?php

namespace App\Modules\Simulation\Services;

use App\Modules\Simulation\Enums\CallStatusEnum;
use App\Modules\Simulation\Repositories\RedisRuntimeStateRepository;

final readonly class QueuePreviewService
{
    public function __construct(
        private RedisRuntimeStateRepository $runtimeStateRepository
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPreview(string $simulationId): array
    {
        $state = $this->runtimeStateRepository->loadState($simulationId);
        $defaults = config('simulation.defaults', []);

        $elevators = array_map(
            static fn ($elevator): array => [
                'elevatorId'           => $elevator->elevatorId,
                'currentFloor'         => $elevator->currentFloor,
                'currentLoad'          => $elevator->currentLoad,
                'capacity'             => $elevator->capacity,
                'pickedUpPassengers'   => $elevator->pickedUpPassengers,
                'droppedOffPassengers' => $elevator->droppedOffPassengers,
                'direction'            => $elevator->direction->value,
                'state'                => $elevator->state->value,
                'condition'            => $elevator->condition->value,
                'doorState'            => $elevator->doorState->value,
                'overloadSavedLoad'    => $elevator->overloadSavedLoad,
                'plannedStops'         => $elevator->plannedStops,
            ],
            $state->elevators,
        );

        $pendingHallCalls = [];
        $waitingPassengers = 0;

        foreach ($state->pendingHallCalls as $call) {
            if ($call->status === CallStatusEnum::Served) {
                continue;
            }

            if ($call->status === CallStatusEnum::Pending) {
                $waitingPassengers++;
            }

            $pendingHallCalls[] = [
                'callId'            => $call->callId,
                'originFloor'       => $call->originFloor,
                'targetFloor'       => $call->targetFloor,
                'direction'         => $call->direction->value,
                'ageTicks'          => $call->ageTicks,
                'status'            => $call->status->value,
                'assignedElevatorId' => $call->assignedElevatorId,
            ];
        }

        return [
            'simulationId'        => $simulationId,
            'tickNumber'          => $state->tickNumber,
            'isEmergencyMode'     => $state->isEmergencyMode,
            'tickIntervalMs'      => $defaults['tickIntervalMs'],
            'floorTravelSeconds'  => $defaults['floorTravelSeconds'],
            'maxPendingCalls'     => $defaults['maxPendingCalls'],
            'waitingPassengers'   => $waitingPassengers,
            'pickedUpPassengers'  => $state->pickedUpPassengers,
            'droppedOffPassengers' => $state->droppedOffPassengers,
            'elevators'           => $elevators,
            'pendingHallCalls'    => $pendingHallCalls,
        ];
    }
}
