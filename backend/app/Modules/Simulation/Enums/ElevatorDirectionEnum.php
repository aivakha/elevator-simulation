<?php


namespace App\Modules\Simulation\Enums;

enum ElevatorDirectionEnum: string
{
    case Up   = 'up';
    case Down = 'down';
    case Idle = 'idle';
}
