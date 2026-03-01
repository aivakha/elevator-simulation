<?php

namespace App\Modules\Simulation\Physics;

use App\Modules\Simulation\Enums\DoorStateEnum;
use App\Modules\Simulation\Enums\ElevatorConditionEnum;
use App\Modules\Simulation\Enums\ElevatorStateEnum;
use App\Modules\Simulation\Runtime\ElevatorRuntimeState;

final class DoorService
{
    public function progress(ElevatorRuntimeState $elevator, int $doorHoldTicks): void
    {
        if ($elevator->condition === ElevatorConditionEnum::OutOfService) {
            $elevator->doorState      = DoorStateEnum::Closed;
            $elevator->doorTimerTicks = 0;
            return;
        }

        if ($elevator->doorState === DoorStateEnum::Opening) {
            $elevator->openDoor($doorHoldTicks);
            return;
        }

        if ($elevator->doorState === DoorStateEnum::Open) {
            // Manual overload: keep the door open until the overload is cleared
            if ($elevator->condition === ElevatorConditionEnum::Overloaded && $elevator->overloadSavedLoad !== null) {
                $elevator->doorTimerTicks = $doorHoldTicks;
                return;
            }

            if ($elevator->doorTimerTicks > 0) {
                $elevator->doorTimerTicks--;
                return;
            }

            $elevator->startClosing();
            return;
        }

        if ($elevator->doorState === DoorStateEnum::Closing) {
            $elevator->doorState      = DoorStateEnum::Closed;
            $elevator->doorTimerTicks = 0;

            if (!$elevator->isMoving()) {
                $elevator->state = ElevatorStateEnum::Idle;
            }
        }
    }
}
