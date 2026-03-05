<?php

namespace App\Modules\Simulation\Runtime;

use App\Modules\Simulation\Enums\DispatchAlgorithmEnum;
use App\Modules\Simulation\Enums\SimulationModeEnum;

final class SimulationRuntimeState
{
    /**
     * @param list<ElevatorRuntimeState> $elevators
     * @param list<HallCallRuntimeState> $pendingHallCalls
     */
    public function __construct(
        public string $simulationId,
        public int $tickNumber,
        public int $floors,
        public int $maxPendingCalls,
        public int $emergencyDescentMultiplier,
        public int $doorHoldTicks,
        public int $tickIntervalMs,
        public int $floorTravelSeconds,
        public int $pickedUpPassengers,
        public int $droppedOffPassengers,
        public SimulationModeEnum $mode,
        public DispatchAlgorithmEnum $algorithm,
        public bool $isEmergencyMode,
        public array $elevators,
        public array $pendingHallCalls,
    ) {
    }

    // Serialization

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'simulationId'               => $this->simulationId,
            'tickNumber'                 => $this->tickNumber,
            'floors'                     => $this->floors,
            'maxPendingCalls'            => $this->maxPendingCalls,
            'emergencyDescentMultiplier' => $this->emergencyDescentMultiplier,
            'doorHoldTicks'              => $this->doorHoldTicks,
            'tickIntervalMs'             => $this->tickIntervalMs,
            'floorTravelSeconds'         => $this->floorTravelSeconds,
            'pickedUpPassengers'         => $this->pickedUpPassengers,
            'droppedOffPassengers'       => $this->droppedOffPassengers,
            'mode'                       => $this->mode->value,
            'algorithm'                  => $this->algorithm->value,
            'isEmergencyMode'            => $this->isEmergencyMode,
            'elevators'                  => array_map(fn($e) => $e->toArray(), $this->elevators),
            'pendingHallCalls'           => array_map(fn($c) => $c->toArray(), $this->pendingHallCalls),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        /** @var array<string, int|string> $defaults */
        $defaults = config('simulation.defaults', []);

        return new self(
            simulationId               : (string) ($payload['simulationId'] ?? ''),
            tickNumber                 : (int) ($payload['tickNumber'] ?? 0),
            floors                      : (int) ($payload['floors'] ?? $defaults['floors']),
            maxPendingCalls            : (int) ($payload['maxPendingCalls'] ?? $defaults['maxPendingCalls']),
            emergencyDescentMultiplier : (int) ($payload['emergencyDescentMultiplier'] ?? $defaults['emergencyDescentMultiplier']),
            doorHoldTicks              : (int) ($payload['doorHoldTicks'] ?? max(1, (int) round((int) $defaults['doorOpenSeconds'] * 1000 / max(1, (int) $defaults['tickIntervalMs'])))),
            tickIntervalMs             : (int) ($payload['tickIntervalMs'] ?? max(1, (int) $defaults['tickIntervalMs'])),
            floorTravelSeconds          : (int) ($payload['floorTravelSeconds'] ?? max(1, (int) $defaults['floorTravelSeconds'])),
            pickedUpPassengers         : (int) ($payload['pickedUpPassengers'] ?? 0),
            droppedOffPassengers       : (int) ($payload['droppedOffPassengers'] ?? 0),
            mode                       : SimulationModeEnum::from((string) ($payload['mode'] ?? $defaults['mode'])),
            algorithm                  : DispatchAlgorithmEnum::from((string) ($payload['algorithm'] ?? $defaults['algorithm'])),
            isEmergencyMode            : (bool) ($payload['isEmergencyMode'] ?? false),
            elevators                  : array_map(ElevatorRuntimeState::fromArray(...), $payload['elevators'] ?? []),
            pendingHallCalls           : array_map(HallCallRuntimeState::fromArray(...), $payload['pendingHallCalls'] ?? []),
        );
    }
}
