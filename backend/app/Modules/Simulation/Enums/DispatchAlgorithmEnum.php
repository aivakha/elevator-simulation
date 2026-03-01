<?php


namespace App\Modules\Simulation\Enums;

enum DispatchAlgorithmEnum: string
{
    case NearestCar = 'nearestCar';
    case Scan       = 'scan';
    case Look       = 'look';
}
