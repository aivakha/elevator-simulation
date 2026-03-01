<?php


namespace App\Modules\Simulation\Enums;

enum DoorStateEnum: string
{
    case Closed  = 'closed';
    case Opening = 'opening';
    case Open    = 'open';
    case Closing = 'closing';
}
