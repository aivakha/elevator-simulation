<?php

namespace App\Modules\Simulation\Controllers;

use Closure;
use App\Http\Controllers\Controller;
use App\Http\Resources\Simulation\ElevatorConditionResource;
use App\Http\Resources\Simulation\ManualCallAssignmentResource;
use App\Http\Resources\Simulation\SimulationConditionResource;
use App\Http\Resources\Simulation\SimulationStatusResource;
use App\Models\Simulation;
use App\Modules\Simulation\DTOs\ManualCallDto;
use App\Modules\Simulation\DTOs\SimulationSnapshotDto;
use App\Modules\Simulation\Enums\ElevatorConditionEnum;
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
    private const string SET_ELEVATOR_CONDITION_UPDATED        = 'updated';
    private const string SET_ELEVATOR_CONDITION_EMERGENCY_MODE = 'emergency_mode';
    private const string SET_ELEVATOR_CONDITION_NOT_FOUND      = 'not_found';

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

    public function queuePreview(Simulation $simulation): JsonResponse
    {
        $state = $this->runtimeStateRepository->loadState($simulation->id);

        return response()->json(SimulationSnapshotDto::fromState($state)->toArray());
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
            return response()->json(ManualCallAssignmentResource::make([
                'assigned' => false,
                'message'  => 'Manual calls are only available in manual mode',
            ])->resolve(), 409);
        }

        if ($assignment === null) {
            return response()->json(ManualCallAssignmentResource::make([
                'assigned' => false,
                'message'  => 'No available elevator for this call',
            ])->resolve(), 409);
        }

        return response()->json(ManualCallAssignmentResource::make([
            'assigned'   => true,
            'assignment' => $assignment,
        ])->resolve());
    }

    public function start(Simulation $simulation): JsonResponse
    {
        $simulation = $this->simulationLifecycleService->start($simulation);

        return response()->json(SimulationStatusResource::make([
            'status' => $simulation->status,
        ])->resolve());
    }

    public function pause(Simulation $simulation): JsonResponse
    {
        $simulation = $this->simulationLifecycleService->pause($simulation);

        return response()->json(SimulationStatusResource::make([
            'status' => $simulation->status,
        ])->resolve());
    }

    public function reset(Simulation $simulation): JsonResponse
    {
        $simulation = $this->withSimulationLock($simulation->id, function () use ($simulation): Simulation {
            return $this->simulationLifecycleService->reset($simulation);
        });

        return response()->json(SimulationStatusResource::make([
            'status' => $simulation->status,
        ])->resolve());
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

        return response()->json(SimulationConditionResource::make([
            'scope'           => 'simulation',
            'condition'       => $condition,
            'isEmergencyMode' => $condition === ElevatorConditionEnum::Emergency->value,
        ])->resolve());
    }

    public function setElevatorCondition(ElevatorConditionRequest $request, Simulation $simulation, string $elevatorId): JsonResponse
    {
        $condition = (string) $request->validated('condition');

        $result = $this->withSimulationLock($simulation->id, function () use ($simulation, $elevatorId, $condition): string {
            $state = $this->runtimeStateRepository->loadState($simulation->id);

            if ($state->isEmergencyMode) {
                return self::SET_ELEVATOR_CONDITION_EMERGENCY_MODE;
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
                return self::SET_ELEVATOR_CONDITION_NOT_FOUND;
            }

            $this->runtimeStateRepository->saveState($state);

            return self::SET_ELEVATOR_CONDITION_UPDATED;
        });

        if ($result === self::SET_ELEVATOR_CONDITION_EMERGENCY_MODE) {
            return response()->json([
                'message' => 'Cannot set elevator condition while simulation emergency mode is active',
            ], 409);
        }

        if ($result === self::SET_ELEVATOR_CONDITION_NOT_FOUND) {
            return response()->json(['message' => 'Elevator not found'], 404);
        }

        return response()->json(ElevatorConditionResource::make([
            'scope'      => 'elevator',
            'elevatorId' => $elevatorId,
            'condition'  => $condition,
        ])->resolve());
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
