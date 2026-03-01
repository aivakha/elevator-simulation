<?php

namespace App\Modules\Simulation\Physics;

use App\Modules\Simulation\Enums\DoorStateEnum;
use App\Modules\Simulation\Enums\ElevatorConditionEnum;
use App\Modules\Simulation\Enums\ElevatorDirectionEnum;
use App\Modules\Simulation\Enums\ElevatorStateEnum;
use App\Modules\Simulation\Runtime\ElevatorRuntimeState;

final class MovementService
{
    /**
     * Advance elevator one tick. Returns the floor number on arrival or null
     */
    public function step(ElevatorRuntimeState $elevator): ?int
    {
        if ($elevator->doorState !== DoorStateEnum::Closed) {
            return null;
        }

        if ($elevator->condition === ElevatorConditionEnum::OutOfService) {
            $elevator->direction = ElevatorDirectionEnum::Idle;
            $elevator->state     = ElevatorStateEnum::Idle;
            return null;
        }

        // Manual overload: halt; open doors once the elevator is fully stopped
        if ($elevator->condition === ElevatorConditionEnum::Overloaded && $elevator->overloadSavedLoad !== null) {
            $wasMoving           = $elevator->isMoving();
            $elevator->direction = ElevatorDirectionEnum::Idle;
            $elevator->state     = ElevatorStateEnum::Idle;

            if (!$wasMoving) {
                $elevator->startOpening();
            }

            return null;
        }

        $nextStop = $elevator->nextStop();

        if ($nextStop === null) {
            $elevator->direction = ElevatorDirectionEnum::Idle;
            $elevator->state     = ElevatorStateEnum::Idle;
            return null;
        }

        if ($elevator->currentFloor === $nextStop) {
            $elevator->removeCurrentStop();
            $elevator->direction = ElevatorDirectionEnum::Idle;
            $elevator->startOpening();
            return $nextStop;
        }

        if ($nextStop > $elevator->currentFloor) {
            $elevator->currentFloor++;
            $elevator->direction = ElevatorDirectionEnum::Up;
            $elevator->state     = ElevatorStateEnum::Moving;
            return null;
        }

        $elevator->currentFloor--;
        $elevator->direction = ElevatorDirectionEnum::Down;
        $elevator->state     = ElevatorStateEnum::Moving;
        return null;
    }
}
