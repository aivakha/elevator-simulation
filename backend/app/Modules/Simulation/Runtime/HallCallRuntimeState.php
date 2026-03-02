<?php

namespace App\Modules\Simulation\Runtime;

use App\Modules\Simulation\Enums\CallStatusEnum;
use App\Modules\Simulation\Enums\HallCallDirectionEnum;
use Random\RandomException;

final class HallCallRuntimeState
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

    // Serialization

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'callId'             => $this->callId,
            'originFloor'        => $this->originFloor,
            'targetFloor'        => $this->targetFloor,
            'direction'          => $this->direction->value,
            'passengerWeight'    => $this->passengerWeight,
            'ageTicks'           => $this->ageTicks,
            'status'             => $this->status->value,
            'assignedElevatorId' => $this->assignedElevatorId,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            callId:             (string) $data['callId'],
            originFloor:        (int) $data['originFloor'],
            targetFloor:        (int) $data['targetFloor'],
            direction:          HallCallDirectionEnum::from((string) $data['direction']),
            passengerWeight:    (int) $data['passengerWeight'],
            ageTicks:           (int) $data['ageTicks'],
            status:             CallStatusEnum::from((string) $data['status']),
            assignedElevatorId: isset($data['assignedElevatorId']) ? (string) $data['assignedElevatorId'] : null,
        );
    }

    /**
     * @throws RandomException
     */
    public static function newId(string $simulationId, int $tickNumber, string $source): string
    {
        return $simulationId . '-' . $source . '-' . $tickNumber . '-' . bin2hex(random_bytes(2));
    }
}
