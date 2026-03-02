<?php

namespace App\Modules\Simulation\DTOs;

use App\Modules\Simulation\Runtime\HallCallRuntimeState;

final readonly class HallCallSnapshotDto
{
    public function __construct(
        public string $callId,
        public int $originFloor,
        public int $targetFloor,
        public string $direction,
        public string $status,
        public ?string $assignedElevatorId,
        public int $ageTicks,
    ) {
    }

    public static function fromRuntimeState(HallCallRuntimeState $call): self
    {
        return new self(
            callId:             $call->callId,
            originFloor:        $call->originFloor,
            targetFloor:        $call->targetFloor,
            direction:          $call->direction->value,
            status:             $call->status->value,
            assignedElevatorId: $call->assignedElevatorId,
            ageTicks:           $call->ageTicks,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'callId'             => $this->callId,
            'originFloor'        => $this->originFloor,
            'targetFloor'        => $this->targetFloor,
            'direction'          => $this->direction,
            'status'             => $this->status,
            'assignedElevatorId' => $this->assignedElevatorId,
            'ageTicks'           => $this->ageTicks,
        ];
    }
}
