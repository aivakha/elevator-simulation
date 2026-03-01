<?php

namespace App\Modules\Simulation\Enums;

enum CallStatusEnum: string
{
    case Pending  = 'pending';
    case Assigned = 'assigned';
    case Riding   = 'riding';
    case Served   = 'served';
}
