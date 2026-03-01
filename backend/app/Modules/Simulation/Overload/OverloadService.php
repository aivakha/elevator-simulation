<?php

namespace App\Modules\Simulation\Overload;

use App\Modules\Simulation\Enums\CallStatusEnum;
use App\Modules\Simulation\Enums\ElevatorConditionEnum;
use App\Modules\Simulation\Runtime\SimulationRuntimeState;

final class OverloadService
{
    /**
     * Evaluate weight-based overload for all elevators
     * Skips elevators with manual overload (condition managed by OverloadControlService)
     */
    public function evaluate(SimulationRuntimeState $state): void
    {
        foreach ($state->elevators as $elevator) {
            // Manual overload: leave condition unchanged; OverloadControlService owns it
            if ($elevator->overloadSavedLoad !== null) {
                continue;
            }

            $isNowOverloaded = $elevator->currentLoad > $elevator->capacity;
            $wasOverloaded   = $elevator->condition === ElevatorConditionEnum::Overloaded;

            if ($isNowOverloaded && !$wasOverloaded) {
                $elevator->condition = ElevatorConditionEnum::Overloaded;
            }

            if (!$isNowOverloaded && $wasOverloaded) {
                $elevator->condition = ElevatorConditionEnum::Normal;
            }

            // Safety valve: overloaded with no stops to drop passengers off — cap the load
            if ($elevator->condition === ElevatorConditionEnum::Overloaded
                && $elevator->plannedStops === []
                && $elevator->currentLoad > $elevator->capacity
            ) {
                $elevator->currentLoad = $elevator->capacity;
                $elevator->condition   = ElevatorConditionEnum::Normal;
            }
        }
    }

    /**
     * Release assigned (not yet boarding) calls from weight-overloaded elevators
     * so they can be re-dispatched to another car.
     */
    public function releaseAssignedCallsForAutoOverloadedElevators(SimulationRuntimeState $state): void
    {
        $autoOverloadedElevators = [];

        foreach ($state->elevators as $elevator) {
            // Auto-overloaded = condition is Overloaded AND not manually triggered
            if ($elevator->condition === ElevatorConditionEnum::Overloaded && $elevator->overloadSavedLoad === null) {
                $autoOverloadedElevators[$elevator->elevatorId] = $elevator;
            }
        }

        if ($autoOverloadedElevators === []) {
            return;
        }

        foreach ($state->pendingHallCalls as $call) {
            if ($call->status !== CallStatusEnum::Assigned || $call->assignedElevatorId === null) {
                continue;
            }

            if (!isset($autoOverloadedElevators[$call->assignedElevatorId])) {
                continue;
            }

            $elevator = $autoOverloadedElevators[$call->assignedElevatorId];
            $call->status             = CallStatusEnum::Pending;
            $call->assignedElevatorId = null;

            $elevator->plannedStops = array_values(
                array_filter(
                    $elevator->plannedStops,
                    static fn (int $floor): bool => $floor !== $call->originFloor,
                ),
            );
        }
    }
}
