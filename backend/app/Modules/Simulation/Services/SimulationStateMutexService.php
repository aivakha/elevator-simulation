<?php

namespace App\Modules\Simulation\Services;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class SimulationStateMutexService
{
    /**
     * @template T
     * @param Closure(): T $callback
     * @return T
     */
    public function withLock(string $simulationId, Closure $callback)
    {
        $key = 'simulation:' . $simulationId . ':mutex';
        $lockSeconds = 10;
        $waitSeconds = 8;

        try {
            return Cache::store('redis')
                ->lock($key, $lockSeconds)
                ->block($waitSeconds, $callback);
        } catch (LockTimeoutException) {
            throw new RuntimeException('Could not acquire simulation state lock');
        }
    }
}
