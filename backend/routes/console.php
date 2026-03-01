<?php

use App\Modules\Simulation\Enums\SimulationStatusEnum;
use App\Modules\Simulation\Services\TickEngineService;
use App\Models\Simulation;
use Illuminate\Support\Facades\Artisan;

Artisan::command('simulation:run-loop {--intervalMs=1000}', function (): int {
    $intervalMs = (int) $this->option('intervalMs');

    if ($intervalMs < 200) {
        $intervalMs = 200;
    }

    if ($intervalMs > 5000) {
        $intervalMs = 5000;
    }

    $tickEngine = app(TickEngineService::class);

    $this->info('Starting simulation loop for all running simulations with interval ' . $intervalMs . 'ms');

    while (true) {
        $loopStartedAt = microtime(true);

        $runningSimulations = Simulation::query()
            ->where('status', SimulationStatusEnum::Running->value)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        foreach ($runningSimulations as $simulationId) {
            try {
                $payload = $tickEngine->runSingleTick((string) $simulationId);
                $this->line('simulation=' . $simulationId . ' tick=' . $payload['tickNumber'] . ' pending=' . count($payload['pendingHallCalls']));
            } catch (Throwable $exception) {
                $this->warn('simulation=' . $simulationId . ' skipped tick: ' . $exception->getMessage());
            }
        }

        $elapsedMs = (int) round((microtime(true) - $loopStartedAt) * 1000);
        $sleepMs = max(0, $intervalMs - $elapsedMs);
        if ($sleepMs > 0) {
            usleep($sleepMs * 1000);
        }
    }

    return self::SUCCESS;
})->purpose('Run continuous simulation ticks');
