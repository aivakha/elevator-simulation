<?php

namespace App\Modules\Simulation\Enums;

enum SimulationStatusEnum: string
{
    case Draft = 'draft';
    case Running = 'running';
    case Paused = 'paused';
    case Completed = 'completed';
}
