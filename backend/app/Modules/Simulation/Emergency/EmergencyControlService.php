<?php

namespace App\Modules\Simulation\Emergency;

use App\Modules\Simulation\Enums\DoorStateEnum;
use App\Modules\Simulation\Enums\ElevatorConditionEnum;
use App\Modules\Simulation\Enums\ElevatorDirectionEnum;
use App\Modules\Simulation\Runtime\SimulationRuntimeState;

final class EmergencyControlService
{
    public function activateRecall(SimulationRuntimeState $state, int $recallFloor = 0): void
    {
        $state->isEmergencyMode = true;
        $descentStep            = max(1, $state->emergencyDescentMultiplier);

        foreach ($state->elevators as $elevator) {
            if ($elevator->condition === ElevatorConditionEnum::OutOfService) {
                $elevator->markOutOfService();
                continue;
            }

            // markEmergency overrides any pending-OOS or overload condition
            $elevator->markEmergency();

            if ($elevator->currentFloor === $recallFloor) {
                $elevator->plannedStops = [];
                $elevator->direction    = ElevatorDirectionEnum::Idle;
                $elevator->openDoor();
                $elevator->currentLoad  = 0;
            } else {
                // Immediately begin descent — overrides any in-progress movement so the
                // elevator never completes an extra floor in the wrong direction
                $elevator->plannedStops   = [$recallFloor];
                $elevator->direction      = ElevatorDirectionEnum::Down;
                $elevator->doorState      = DoorStateEnum::Closed;
                $elevator->doorTimerTicks = 0;
                $elevator->currentFloor   = max($recallFloor, $elevator->currentFloor - $descentStep);

                if ($elevator->currentFloor === $recallFloor) {
                    $elevator->plannedStops = [];
                    $elevator->direction    = ElevatorDirectionEnum::Idle;
                    $elevator->currentLoad  = 0;
                    $elevator->startOpening();
                }
            }
        }

        $state->pendingHallCalls = [];
    }

    public function clearRecall(SimulationRuntimeState $state): void
    {
        $state->isEmergencyMode = false;

        foreach ($state->elevators as $elevator) {
            if ($elevator->condition !== ElevatorConditionEnum::Emergency) {
                continue;
            }

            $elevator->clearEmergency();

            if ($elevator->doorState === DoorStateEnum::Open) {
                $elevator->startClosing();
            }
        }
    }
}
