<?php

namespace App\Modules\Simulation\Repositories;

use App\Modules\Simulation\Runtime\SimulationRuntimeState;
use Illuminate\Support\Facades\Redis;
use RuntimeException;

final class RedisRuntimeStateRepository
{
    public function loadState(string $simulationId): SimulationRuntimeState
    {
        $rawState = Redis::get($this->stateKey($simulationId));

        if ($rawState === null) {
            throw new RuntimeException(
                message : "No runtime state found for simulation {$simulationId}. Ensure the simulation has been started"
            );
        }

        $decoded = json_decode($rawState, true);

        if (!is_array($decoded)) {
            throw new RuntimeException(
                message : "Corrupt runtime state for simulation {$simulationId}"
            );
        }

        return SimulationRuntimeState::fromArray($decoded);
    }

    public function saveState(SimulationRuntimeState $state): void
    {
        $encoded = json_encode($state->toArray());
        Redis::set($this->stateKey($state->simulationId), $encoded);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function publishTick(string $simulationId, array $payload): void
    {
        $encoded    = json_encode($payload);
        $historyKey = $this->historyKey($simulationId);
        $channel    = $this->channelName($simulationId);

        Redis::lpush($historyKey, $encoded);
        Redis::ltrim($historyKey, 0, 199);
        Redis::publish($channel, $encoded);
    }

    private function stateKey(string $simulationId): string
    {
        return 'simulation:' . $simulationId . ':state';
    }

    private function historyKey(string $simulationId): string
    {
        return 'simulation:' . $simulationId . ':ticks';
    }

    private function channelName(string $simulationId): string
    {
        return 'simulation:' . $simulationId . ':ticks';
    }
}
