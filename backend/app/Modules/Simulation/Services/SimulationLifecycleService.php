<?php

namespace App\Modules\Simulation\Services;

use App\Models\Simulation;
use App\Models\SimulationRun;
use App\Modules\Simulation\DTOs\SimulationConfigDto;
use App\Modules\Simulation\DTOs\SimulationSnapshotDto;
use App\Modules\Simulation\Enums\SimulationStatusEnum;
use App\Modules\Simulation\Repositories\RedisRuntimeStateRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final readonly class SimulationLifecycleService
{
    public function __construct(
        private RedisRuntimeStateRepository $runtimeStateRepository,
        private RuntimeStateFactory $runtimeStateFactory,
    ) {
    }

    public function create(string $name, SimulationConfigDto $config): Simulation
    {
        return Simulation::query()->create([
            'id'         => (string) Str::uuid(),
            'name'       => $name,
            'status'     => SimulationStatusEnum::Draft->value,
            'config_json' => $config->toArray(),
        ]);
    }

    public function list(): array
    {
        return Simulation::query()->orderByDesc('created_at')->get()->all();
    }

    public function start(Simulation $simulation): Simulation
    {
        $simulation->status = SimulationStatusEnum::Running->value;
        $simulation->save();

        $hasOpenRun = SimulationRun::query()
            ->where('simulation_id', $simulation->id)
            ->whereNull('ended_at')
            ->exists();

        if (!$hasOpenRun) {
            SimulationRun::query()->create([
                'id'            => (string) Str::uuid(),
                'simulation_id' => $simulation->id,
                'started_at'    => CarbonImmutable::now(),
                'total_ticks'   => 0,
            ]);
        }

        return $simulation;
    }

    public function pause(Simulation $simulation): Simulation
    {
        $simulation->status = SimulationStatusEnum::Paused->value;
        $simulation->save();

        return $simulation;
    }

    public function reset(Simulation $simulation): Simulation
    {
        // Close any active run. If rows were affected the simulation was
        // running or paused, so transition through Completed before Draft
        // to record a clean end-of-run in the status history
        $closedRuns = SimulationRun::query()
            ->where('simulation_id', $simulation->id)
            ->whereNull('ended_at')
            ->update(['ended_at' => CarbonImmutable::now()]);

        if ($closedRuns > 0) {
            $simulation->status = SimulationStatusEnum::Completed->value;
            $simulation->save();
        }

        $simulation->status = SimulationStatusEnum::Draft->value;
        $simulation->save();

        $config = is_array($simulation->config_json) ? $simulation->config_json : [];
        $state = $this->runtimeStateFactory->create($simulation->id, $config);

        $this->runtimeStateRepository->saveState($state);
        $this->runtimeStateRepository->publishTick($simulation->id, SimulationSnapshotDto::fromState($state)->toArray());

        return $simulation;
    }
}
