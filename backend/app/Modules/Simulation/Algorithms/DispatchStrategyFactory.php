<?php


namespace App\Modules\Simulation\Algorithms;

use App\Modules\Simulation\Enums\DispatchAlgorithmEnum;

final class DispatchStrategyFactory
{
    public function make(DispatchAlgorithmEnum $algorithm): DispatchStrategyInterface
    {
        return match ($algorithm) {
            DispatchAlgorithmEnum::NearestCar => new NearestCarStrategy(),
            DispatchAlgorithmEnum::Scan       => new ScanStrategy(),
            DispatchAlgorithmEnum::Look       => new LookStrategy(),
        };
    }
}
