<?php

namespace App\Modules\Simulation\Services;

use App\Modules\Simulation\Enums\DispatchAlgorithmEnum;
use App\Modules\Simulation\Enums\DoorStateEnum;
use App\Modules\Simulation\Enums\ElevatorConditionEnum;
use App\Modules\Simulation\Enums\ElevatorDirectionEnum;
use App\Modules\Simulation\Enums\ElevatorStateEnum;
use App\Modules\Simulation\Enums\SimulationModeEnum;
use App\Modules\Simulation\Runtime\ElevatorRuntimeState;
use App\Modules\Simulation\Runtime\SimulationRuntimeState;
use App\Modules\Simulation\Zones\ZoneCalculator;

final class RuntimeStateFactory
{
    private const string KEY_FLOORS                       = 'floors';
    private const string KEY_ELEVATORS                    = 'elevators';
    private const string KEY_CAPACITY                     = 'capacityPerElevator';
    private const string KEY_DOOR_OPEN_SECONDS            = 'doorOpenSeconds';
    private const string KEY_MODE                         = 'mode';
    private const string KEY_ALGORITHM                    = 'algorithm';
    private const string KEY_EMERGENCY_DESCENT_MULTIPLIER = 'emergencyDescentMultiplier';
    private const string KEY_TICK_INTERVAL_MS             = 'tickIntervalMs';
    private const string KEY_MAX_PENDING_CALLS            = 'maxPendingCalls';

    /**
     * @param array<string, mixed> $config
     */
    public function create(string $simulationId, array $config): SimulationRuntimeState
    {
        $limits   = config('simulation.limits', []);
        $defaults = config('simulation.defaults', []);

        $floors = $this->resolveClampedConfigInt(
            config       : $config,
            defaults    : $defaults,
            limits      : $limits,
            key         : self::KEY_FLOORS,
            minLimitKey : 'minFloors',
            maxLimitKey : 'maxFloors',
        );

        $elevatorCount = $this->resolveClampedConfigInt(
            config       : $config,
            defaults    : $defaults,
            limits      : $limits,
            key         : self::KEY_ELEVATORS,
            minLimitKey :'minElevators',
            maxLimitKey : 'maxElevators',
        );

        $capacity = $this->resolveClampedConfigInt(
            config       : $config,
            defaults    : $defaults,
            limits      : $limits,
            key         : self::KEY_CAPACITY,
            minLimitKey : 'minCapacityPerElevator',
            maxLimitKey : 'maxCapacityPerElevator',
        );

        $emergencyDescentMultiplier = $this->resolveClampedConfigInt(
            config       : $config,
            defaults    : $defaults,
            limits      : $limits,
            key         : self::KEY_EMERGENCY_DESCENT_MULTIPLIER,
            minLimitKey : 'minEmergencyDescentMultiplier',
            maxLimitKey : 'maxEmergencyDescentMultiplier',
        );

        $doorOpenSeconds = $this->resolveClampedConfigInt(
            config       : $config,
            defaults    : $defaults,
            limits      : $limits,
            key         : self::KEY_DOOR_OPEN_SECONDS,
            minLimitKey : 'minDoorOpenSeconds',
            maxLimitKey : 'maxDoorOpenSeconds',
        );

        $tickIntervalMs    = $this->resolveDefaultPositiveInt($defaults, self::KEY_TICK_INTERVAL_MS);
        $floorTravelSeconds = $this->resolveDefaultPositiveInt($defaults, 'floorTravelSeconds');
        $maxPendingCalls   = $this->resolveDefaultPositiveInt($defaults, self::KEY_MAX_PENDING_CALLS);
        $doorHoldTicks     = (int) round(($doorOpenSeconds * 1000) / $tickIntervalMs);

        $mode      = SimulationModeEnum::from($config[self::KEY_MODE] ?? $defaults[self::KEY_MODE]);
        $algorithm = DispatchAlgorithmEnum::from($config[self::KEY_ALGORITHM] ?? $defaults[self::KEY_ALGORITHM]);
        $elevators = $this->buildElevators($floors, $elevatorCount, $capacity);

        return new SimulationRuntimeState(
            simulationId               : $simulationId,
            tickNumber                 : 0,
            floors                     : $floors,
            maxPendingCalls            : $maxPendingCalls,
            emergencyDescentMultiplier : $emergencyDescentMultiplier,
            doorHoldTicks              : $doorHoldTicks,
            tickIntervalMs             : $tickIntervalMs,
            floorTravelSeconds         : $floorTravelSeconds,
            pickedUpPassengers         : 0,
            droppedOffPassengers       : 0,
            mode                       : $mode,
            algorithm                  : $algorithm,
            isEmergencyMode            : false,
            elevators                  : $elevators,
            pendingHallCalls           : [],
        );
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, int|string> $defaults
     * @param array<string, int> $limits
     */
    private function resolveClampedConfigInt(
        array $config,
        array $defaults,
        array $limits,
        string $key,
        string $minLimitKey,
        string $maxLimitKey,
    ): int {
        $value = (int) ($config[$key] ?? $defaults[$key]);
        $min   = $limits[$minLimitKey];
        $max   = $limits[$maxLimitKey];

        return max($min, min($max, $value));
    }

    /**
     * @param array<string, int|string> $defaults
     */
    private function resolveDefaultPositiveInt(array $defaults, string $key): int
    {
        return max(1, (int) $defaults[$key]);
    }

    /**
     * @return list<ElevatorRuntimeState>
     */
    private function buildElevators(int $floors, int $elevatorCount, int $capacity): array
    {
        $elevators = [];

        for ($index = 0; $index < $elevatorCount; $index++) {
            $zoneAnchorFloor = ZoneCalculator::anchorFloor($index, $elevatorCount, $floors);

            $elevators[] = new ElevatorRuntimeState(
                elevatorId           : 'E' . ($index + 1),
                shaftNumber          : $index,
                currentFloor         : $zoneAnchorFloor,
                currentLoad          : 0,
                capacity             : $capacity,
                pickedUpPassengers   : 0,
                droppedOffPassengers : 0,
                overloadSavedLoad    : null,
                direction            : ElevatorDirectionEnum::Idle,
                state                : ElevatorStateEnum::Idle,
                condition            : ElevatorConditionEnum::Normal,
                doorState            : DoorStateEnum::Closed,
                doorTimerTicks       : 0,
                plannedStops         : [],
            );
        }

        return $elevators;
    }
}
