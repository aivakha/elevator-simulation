<?php

namespace App\Modules\Simulation\Controllers;

use Closure;
use App\Http\Controllers\Controller;
use App\Models\Simulation;
use App\Modules\Simulation\DTOs\ManualCallDto;
use App\Modules\Simulation\Enums\ElevatorConditionEnum;
use App\Modules\Simulation\Enums\ElevatorConditionUpdateResult;
use App\Modules\Simulation\Enums\SimulationModeEnum;
use App\Modules\Simulation\Calls\DispatchService;
use App\Modules\Simulation\Overload\OverloadControlService;
use App\Modules\Simulation\Repositories\RedisRuntimeStateRepository;
use App\Modules\Simulation\Requests\ElevatorConditionRequest;
use App\Modules\Simulation\Requests\ManualCallRequest;
use App\Modules\Simulation\Requests\SimulationConditionRequest;
use App\Modules\Simulation\Emergency\EmergencyControlService;
use App\Modules\Simulation\OutOfService\OutOfServiceControlService;
use App\Modules\Simulation\Services\SimulationLifecycleService;
use App\Modules\Simulation\Services\SimulationStateMutexService;
use RuntimeException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class SimulationControlController extends Controller
{
    public function __construct(
        private readonly RedisRuntimeStateRepository $runtimeStateRepository,
        private readonly DispatchService $dispatchService,
        private readonly SimulationLifecycleService $simulationLifecycleService,
        private readonly SimulationStateMutexService $simulationStateMutexService,
        private readonly EmergencyControlService $emergencyControlService,
        private readonly OverloadControlService $overloadControlService,
        private readonly OutOfServiceControlService $outOfServiceControlService,
    ) {
    }

    public function enqueueManualCall(ManualCallRequest $request, Simulation $simulation): JsonResponse
    {
        $manualCall = ManualCallDto::fromArray($request->validated());
        $assignment = $this->withSimulationLock($simulation->id, function () use ($simulation, $manualCall) {
            $state = $this->runtimeStateRepository->loadState($simulation->id);

            if ($state->mode !== SimulationModeEnum::Manual) {
                return false;
            }

            $assignment = $this->dispatchService->assignManualCall($state, $manualCall);
            $this->runtimeStateRepository->saveState($state);
            return $assignment;
        });

        if ($assignment === false) {
            return response()->json([
                'assigned' => false,
                'message'  => 'Manual calls are only available in manual mode',
            ], 409);
        }

        if ($assignment === null) {
            return response()->json([
                'assigned' => false,
                'message'  => 'No available elevator for this call',
            ], 409);
        }

        return response()->json([
            'assigned'   => true,
            'assignment' => $assignment,
        ]);
    }

    public function start(Simulation $simulation): JsonResponse
    {
        $simulation = $this->simulationLifecycleService->start($simulation);

        return response()->json(['status' => $simulation->status]);
    }

    public function pause(Simulation $simulation): JsonResponse
    {
        $simulation = $this->simulationLifecycleService->pause($simulation);

        return response()->json(['status' => $simulation->status]);
    }

    public function reset(Simulation $simulation): JsonResponse
    {
        $simulation = $this->withSimulationLock($simulation->id, function () use ($simulation): Simulation {
            return $this->simulationLifecycleService->reset($simulation);
        });

        return response()->json(['status' => $simulation->status]);
    }

    public function setSimulationCondition(SimulationConditionRequest $request, Simulation $simulation): JsonResponse
    {
        $condition = (string) $request->validated('condition');

        $this->withSimulationLock($simulation->id, function () use ($simulation, $condition): void {
            $state = $this->runtimeStateRepository->loadState($simulation->id);

            $condition === ElevatorConditionEnum::Emergency->value
                ? $this->emergencyControlService->activateRecall($state)
                : $this->emergencyControlService->clearRecall($state);

            $this->runtimeStateRepository->saveState($state);
        });

        return response()->json([
            'scope'           => 'simulation',
            'condition'       => $condition,
            'isEmergencyMode' => $condition === ElevatorConditionEnum::Emergency->value,
        ]);
    }

    public function setElevatorCondition(ElevatorConditionRequest $request, Simulation $simulation, string $elevatorId): JsonResponse
    {
        $condition = (string) $request->validated('condition');

        $result = $this->withSimulationLock($simulation->id, function () use ($simulation, $elevatorId, $condition): ElevatorConditionUpdateResult {
            $state = $this->runtimeStateRepository->loadState($simulation->id);

            if ($state->isEmergencyMode) {
                return ElevatorConditionUpdateResult::EmergencyMode;
            }

            $changed = false;

            if ($condition === ElevatorConditionEnum::Normal->value) {
                $changed = $this->overloadControlService->clear($state, $elevatorId);
                $changed = $this->outOfServiceControlService->enable($state, $elevatorId) || $changed;
            } elseif ($condition === ElevatorConditionEnum::Overloaded->value) {
                $changed = $this->overloadControlService->activate($state, $elevatorId);
            } elseif ($condition === ElevatorConditionEnum::PendingOutOfService->value) {
                $changed = $this->outOfServiceControlService->disable($state, $elevatorId);
            }

            if (!$changed) {
                return ElevatorConditionUpdateResult::NotFound;
            }

            $this->runtimeStateRepository->saveState($state);

            return ElevatorConditionUpdateResult::Updated;
        });

        if ($result === ElevatorConditionUpdateResult::EmergencyMode) {
            return response()->json([
                'message' => 'Cannot set elevator condition while simulation emergency mode is active',
            ], 409);
        }

        if ($result === ElevatorConditionUpdateResult::NotFound) {
            return response()->json(['message' => 'Elevator not found'], 404);
        }

        return response()->json([
            'scope'      => 'elevator',
            'elevatorId' => $elevatorId,
            'condition'  => $condition,
        ]);
    }

    /**
     * @template T
     * @param Closure(): T $callback
     * @return T
     */
    private function withSimulationLock(string $simulationId, Closure $callback)
    {
        try {
            return $this->simulationStateMutexService->withLock($simulationId, $callback);
        } catch (RuntimeException $exception) {
            throw new HttpException(409, 'Simulation is busy, try again in a moment');
        }
    }
}
