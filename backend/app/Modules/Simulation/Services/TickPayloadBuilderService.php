<?php

namespace App\Modules\Simulation\Services;

use App\Modules\Simulation\Enums\CallStatusEnum;
use App\Modules\Simulation\Runtime\SimulationRuntimeState;

final class TickPayloadBuilderService
{
    /**
     * @return array<string, mixed>
     */
    public function build(SimulationRuntimeState $state): array
    {
        /** @var array<string, int|string> $defaults */
        $defaults = config('simulation.defaults', []);
        $assignedPickupCountsByElevator = [];

        foreach ($state->pendingHallCalls as $call) {
            if ($call->status !== CallStatusEnum::Assigned || $call->assignedElevatorId === null) {
                continue;
            }

            $assignedPickupCountsByElevator[$call->assignedElevatorId] =
                ($assignedPickupCountsByElevator[$call->assignedElevatorId] ?? 0) + 1;
        }

        $waitingPassengers = array_reduce(
            $state->pendingHallCalls,
            static fn (int $carry, $call): int => $carry + ($call->status === CallStatusEnum::Pending ? 1 : 0),
            0,
        );

        return [
            'simulationId'      => $state->simulationId,
            'tickNumber'        => $state->tickNumber,
            'mode'              => $state->mode->value,
            'algorithm'         => $state->algorithm->value,
            'isEmergencyMode'   => $state->isEmergencyMode,
            'tickIntervalMs'    => max(1, (int) $defaults['tickIntervalMs']),
            'floorTravelSeconds' => max(1, (int) $defaults['floorTravelSeconds']),
            'maxPendingCalls'   => max(1, (int) $defaults['maxPendingCalls']),
            'waitingPassengers' => $waitingPassengers,
            'pickedUpPassengers'  => $state->pickedUpPassengers,
            'droppedOffPassengers' => $state->droppedOffPassengers,
            'totalPassengers'     => $waitingPassengers + $state->pickedUpPassengers + $state->droppedOffPassengers,
            'elevators'           => array_map(
                static fn ($e): array => [
                    'elevatorId'          => $e->elevatorId,
                    'currentFloor'        => $e->currentFloor,
                    'currentLoad'         => $e->currentLoad,
                    'capacity'            => $e->capacity,
                    'assignedPickupCount' => (int) ($assignedPickupCountsByElevator[$e->elevatorId] ?? 0),
                    'pickedUpPassengers'  => $e->pickedUpPassengers,
                    'droppedOffPassengers' => $e->droppedOffPassengers,
                    'direction'           => $e->direction->value,
                    'state'               => $e->state->value,
                    'condition'           => $e->condition->value,
                    'doorState'           => $e->doorState->value,
                    'overloadSavedLoad'   => $e->overloadSavedLoad,
                    'plannedStops'        => $e->plannedStops,
                ],
                $state->elevators,
            ),
            'pendingHallCalls'    => array_map(
                static fn ($call): array => [
                    'callId'             => $call->callId,
                    'originFloor'        => $call->originFloor,
                    'targetFloor'        => $call->targetFloor,
                    'direction'          => $call->direction->value,
                    'status'             => $call->status->value,
                    'assignedElevatorId' => $call->assignedElevatorId,
                    'ageTicks'           => $call->ageTicks,
                ],
                $state->pendingHallCalls,
            ),
        ];
    }
}
