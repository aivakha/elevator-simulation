<?php


namespace App\Modules\Simulation\Enums;

enum HallCallDirectionEnum: string
{
    case Up = 'up';
    case Down = 'down';

    public static function fromFloors(int $originFloor, int $targetFloor): self
    {
        return $targetFloor > $originFloor ? self::Up : self::Down;
    }
}
