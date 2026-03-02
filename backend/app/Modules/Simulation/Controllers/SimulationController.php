<?php

namespace App\Modules\Simulation\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Simulation\SimulationConfigOptionsResource;
use App\Http\Resources\Simulation\SimulationResource;
use App\Models\Simulation;
use App\Modules\Simulation\DTOs\SimulationConfigDto;
use App\Modules\Simulation\Requests\SimulationConfigRequest;
use App\Modules\Simulation\Services\SimulationLifecycleService;
use Illuminate\Http\JsonResponse;

final class SimulationController extends Controller
{
    public function __construct(private readonly SimulationLifecycleService $simulationLifecycleService)
    {
    }

    public function listSimulations(): JsonResponse
    {
        return response()->json([
            'simulations' => SimulationResource::collection($this->simulationLifecycleService->list())->resolve(),
        ]);
    }

    public function createSimulation(SimulationConfigRequest $request): JsonResponse
    {
        $payload    = $request->validated();
        $providedId = $payload['id'] ?? null;

        if (is_string($providedId) && Simulation::query()->find($providedId) !== null) {
            return response()->json(['message' => 'Simulation already exists'], 409);
        }

        $name  = $payload['name'] ?? config('simulation.defaults.name');
        $config = SimulationConfigDto::fromArray($payload);

        if (is_string($providedId) && $providedId !== '') {
            $simulation = Simulation::query()->create([
                'id'         => $providedId,
                'name'       => $name,
                'status'     => 'draft',
                'config_json' => $config->toArray(),
            ]);
        } else {
            $simulation = $this->simulationLifecycleService->create($name, $config);
        }

        $simulation = $this->simulationLifecycleService->reset($simulation);

        return response()->json([
            'simulation' => SimulationResource::make($simulation)->resolve(),
        ]);
    }

    public function updateSimulationConfig(SimulationConfigRequest $request, Simulation $simulation): JsonResponse
    {
        $payload = $request->validated();
        $config   = SimulationConfigDto::fromArray($payload);
        $name    = trim($payload['name'] ?? $simulation->name);

        $simulation->name = $name !== '' ? $name : $simulation->name;
        $simulation->config_json = $config->toArray();
        $simulation->save();

        $simulation = $this->simulationLifecycleService->reset($simulation);

        return response()->json([
            'simulation' => SimulationResource::make($simulation)->resolve(),
        ]);
    }

    public function deleteSimulation(Simulation $simulation): JsonResponse
    {
        $simulation->delete();

        return response()->json(['deleted' => true]);
    }

    public function configOptions(): JsonResponse
    {
        return response()->json(SimulationConfigOptionsResource::make([
            'defaults' => Simulation::defaultConfigPayload(),
            'limits'   => config('simulation.limits', []),
        ])->resolve());
    }
}
