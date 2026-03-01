<?php

namespace App\Modules\Simulation\Enums;

/**
 * Represents the physical motion state of an elevator car.
 * For operational condition (emergency, overloaded, out-of-service)
 * see ElevatorConditionEnum — the two axes are orthogonal.
 */
enum ElevatorStateEnum: string
{
    case Idle   = 'Idle';
    case Moving = 'Moving';
}
