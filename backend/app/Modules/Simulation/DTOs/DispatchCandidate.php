<?php


namespace App\Modules\Simulation\DTOs;

final class DispatchCandidate
{
    public function __construct(
        public readonly string $elevatorId,
        public readonly int $priorityScore,
    ) {
    }
}
