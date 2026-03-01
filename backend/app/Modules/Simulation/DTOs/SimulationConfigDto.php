<?php


namespace App\Modules\Simulation\DTOs;

use App\Modules\Simulation\Enums\DispatchAlgorithmEnum;
use App\Modules\Simulation\Enums\SimulationModeEnum;

final readonly class SimulationConfigDto
{
    public function __construct(
        public int $floors,
        public int $elevators,
        public int $capacityPerElevator,
        public int $doorOpenSeconds,
        public int $emergencyDescentMultiplier,
        public SimulationModeEnum $mode,
        public DispatchAlgorithmEnum $algorithm,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $defaults = config('simulation.defaults', []);

        return new self(
            floors                      : (int) ($payload['floors'] ?? $defaults['floors']),
            elevators                  : (int) ($payload['elevators'] ?? $defaults['elevators']),
            capacityPerElevator        : (int) ($payload['capacityPerElevator'] ?? $defaults['capacityPerElevator']),
            doorOpenSeconds            : (int) ($payload['doorOpenSeconds'] ?? $defaults['doorOpenSeconds']),
            emergencyDescentMultiplier : (int) $defaults['emergencyDescentMultiplier'],
            mode                       : SimulationModeEnum::from((string) ($payload['mode'] ?? $defaults['mode'])),
            algorithm                  : DispatchAlgorithmEnum::from((string) ($payload['algorithm'] ?? $defaults['algorithm'])),
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            'floors'                      => $this->floors,
            'elevators'                  => $this->elevators,
            'capacityPerElevator'        => $this->capacityPerElevator,
            'doorOpenSeconds'            => $this->doorOpenSeconds,
            'emergencyDescentMultiplier' => $this->emergencyDescentMultiplier,
            'mode'                       => $this->mode->value,
            'algorithm'                  => $this->algorithm->value,
        ];
    }
}
