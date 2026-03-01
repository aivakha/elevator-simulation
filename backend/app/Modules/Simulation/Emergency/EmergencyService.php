<?php

namespace App\Modules\Simulation\Emergency;

use App\Modules\Simulation\Enums\DoorStateEnum;
use App\Modules\Simulation\Enums\ElevatorConditionEnum;
use App\Modules\Simulation\Enums\ElevatorDirectionEnum;
use App\Modules\Simulation\Enums\ElevatorStateEnum;
use App\Modules\Simulation\Runtime\SimulationRuntimeState;

final class EmergencyService
{
    /**
     * @return list<array<string, int|string>>
     */
    public function stepRecallTick(SimulationRuntimeState $state): array
    {
        $arrivals    = [];
        $descentStep = max(1, $state->emergencyDescentMultiplier);

        foreach ($state->elevators as $elevator) {
            if ($elevator->condition === ElevatorConditionEnum::OutOfService) {
                $elevator->markOutOfService();
                continue;
            }

            $elevator->markEmergency();

            if ($elevator->currentFloor === 0) {
                $elevator->plannedStops = [];
                $elevator->currentLoad  = 0;
                $elevator->direction    = ElevatorDirectionEnum::Idle;
                $elevator->state        = ElevatorStateEnum::Idle;

                if ($elevator->doorState === DoorStateEnum::Closed || $elevator->doorState === DoorStateEnum::Closing) {
                    $elevator->startOpening();
                    continue;
                }

                if ($elevator->doorState === DoorStateEnum::Opening) {
                    $elevator->openDoor();
                }

                continue;
            }

            $elevator->plannedStops   = [0];
            $elevator->direction      = ElevatorDirectionEnum::Down;
            $elevator->state          = ElevatorStateEnum::Moving;
            $elevator->doorState      = DoorStateEnum::Closed;
            $elevator->doorTimerTicks = 0;
            $elevator->currentFloor   = max(0, $elevator->currentFloor - $descentStep);

            if ($elevator->currentFloor !== 0) {
                continue;
            }

            $elevator->plannedStops = [];
            $elevator->currentLoad  = 0;
            $elevator->direction    = ElevatorDirectionEnum::Idle;
            $elevator->state        = ElevatorStateEnum::Idle;
            $elevator->startOpening();

            $arrivals[] = [
                'elevatorId' => $elevator->elevatorId,
                'floor'       => 0,
            ];
        }

        return $arrivals;
    }
}
