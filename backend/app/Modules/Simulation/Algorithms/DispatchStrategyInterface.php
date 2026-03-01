<?php


namespace App\Modules\Simulation\Algorithms;

use App\Modules\Simulation\DTOs\DispatchCandidate;
use App\Modules\Simulation\DTOs\ManualCallDto;
use App\Modules\Simulation\Runtime\ElevatorRuntimeState;

interface DispatchStrategyInterface
{
    /**
     * @param list<ElevatorRuntimeState> $elevators
     */
    public function selectCar(array $elevators, ManualCallDto $call): ?DispatchCandidate;
}
