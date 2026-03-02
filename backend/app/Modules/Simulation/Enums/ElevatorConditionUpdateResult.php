<?php

namespace App\Modules\Simulation\Enums;

enum ElevatorConditionUpdateResult
{
    case Updated;
    case EmergencyMode;
    case NotFound;
}
