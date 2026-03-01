<?php

namespace App\Modules\Simulation\Runtime;

use App\Modules\Simulation\Enums\CallStatusEnum;
use App\Modules\Simulation\Enums\HallCallDirectionEnum;
use Random\RandomException;

final class HallCallState
{
    public function __construct(
        public string $callId,
        public int $originFloor,
        public int $targetFloor,
        public HallCallDirectionEnum $direction,
        public int $passengerWeight,
        public int $ageTicks = 0,
        public CallStatusEnum $status = CallStatusEnum::Pending,
        public ?string $assignedElevatorId = null,
    ) {
    }

    /**
     * @throws RandomException
     */
    public static function newId(string $simulationId, int $tickNumber, string $source): string
    {
        return $simulationId . '-' . $source . '-' . $tickNumber . '-' . bin2hex(random_bytes(2));
    }
}
