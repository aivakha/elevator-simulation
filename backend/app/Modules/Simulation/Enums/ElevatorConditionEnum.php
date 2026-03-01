<?php

namespace App\Modules\Simulation\Enums;

/**
 * Represents the operational condition/feature state of an elevator.
 * Orthogonal to ElevatorStateEnum (physical motion): an elevator can be
 * Moving + Emergency, or Idle + Overloaded, etc.
 */
enum ElevatorConditionEnum: string
{
    case Normal              = 'Normal';
    case Emergency           = 'Emergency';
    case Overloaded          = 'Overloaded';
    case PendingOutOfService = 'PendingOutOfService';
    case OutOfService        = 'OutOfService';
}
