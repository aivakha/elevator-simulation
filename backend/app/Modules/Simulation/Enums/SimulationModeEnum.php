<?php


namespace App\Modules\Simulation\Enums;

enum SimulationModeEnum: string
{
    case MorningPeak = 'morningPeak';
    case EveningPeak = 'eveningPeak';
    case Regular     = 'regular';
    case Manual      = 'manual';
}
