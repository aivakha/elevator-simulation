<?php


namespace App\Modules\Simulation\DTOs;

final readonly class ManualCallDto
{
    public function __construct(
        public int $originFloor,
        public int $destinationFloor,
        public int $passengerWeight,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            originFloor      : (int) ($payload['originFloor'] ?? 0),
            destinationFloor : (int) ($payload['destinationFloor'] ?? 0),
            passengerWeight  : (int) ($payload['passengerWeight'] ?? 180),
        );
    }
}
