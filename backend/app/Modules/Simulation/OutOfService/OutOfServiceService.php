<?php

namespace App\Modules\Simulation\OutOfService;

use App\Modules\Simulation\Enums\CallStatusEnum;
use App\Modules\Simulation\Enums\ElevatorConditionEnum;
use App\Modules\Simulation\Runtime\SimulationRuntimeState;
use App\Modules\Simulation\Zones\ZoneCalculator;

final class OutOfServiceService
{
    /**
     * Advance pending-out-of-service elevators through their lifecycle:
     * 1. Finish any in-progress rides (passengers already aboard)
     * 2. Return to the zone anchor floor
     * 3. Mark as fully out of service once parked
     */
    public function progressTransitions(SimulationRuntimeState $state): void
    {
        $elevatorCount = count($state->elevators);
        if ($elevatorCount === 0) {
            return;
        }

        foreach ($state->elevators as $elevator) {
            if ($elevator->condition !== ElevatorConditionEnum::PendingOutOfService) {
                continue;
            }

            $elevator->plannedStops = [];
            $ridingDestinations     = [];

            foreach ($state->pendingHallCalls as $call) {
                if ($call->assignedElevatorId !== $elevator->elevatorId || $call->status !== CallStatusEnum::Riding) {
                    continue;
                }

                $ridingDestinations[] = $call->targetFloor;
                $elevator->appendStop($call->targetFloor);
            }

            if ($ridingDestinations !== []) {
                continue;
            }

            $zoneAnchorFloor = ZoneCalculator::anchorFloor($elevator->shaftNumber, $elevatorCount, $state->floors);

            if ($elevator->currentFloor !== $zoneAnchorFloor) {
                $elevator->appendStop($zoneAnchorFloor);
                continue;
            }

            $elevator->markOutOfService();
        }
    }
}
