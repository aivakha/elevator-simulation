<?php

namespace App\Modules\Simulation\DTOs;

use App\Modules\Simulation\Runtime\ElevatorRuntimeState;

final readonly class ElevatorSnapshotDto
{
    /**
     * @param list<int> $plannedStops
     */
    public function __construct(
        public string $elevatorId,
        public int $currentFloor,
        public int $currentLoad,
        public int $capacity,
        public int $assignedPickupCount,
        public int $pickedUpPassengers,
        public int $droppedOffPassengers,
        public string $direction,
        public string $state,
        public string $condition,
        public string $doorState,
        public ?int $overloadSavedLoad,
        public array $plannedStops,
    ) {
    }

    public static function fromRuntimeState(ElevatorRuntimeState $elevator, int $assignedPickupCount): self
    {
        return new self(
            elevatorId:           $elevator->elevatorId,
            currentFloor:         $elevator->currentFloor,
            currentLoad:          $elevator->currentLoad,
            capacity:             $elevator->capacity,
            assignedPickupCount:  $assignedPickupCount,
            pickedUpPassengers:   $elevator->pickedUpPassengers,
            droppedOffPassengers: $elevator->droppedOffPassengers,
            direction:            $elevator->direction->value,
            state:                $elevator->state->value,
            condition:            $elevator->condition->value,
            doorState:            $elevator->doorState->value,
            overloadSavedLoad:    $elevator->overloadSavedLoad,
            plannedStops:         $elevator->plannedStops,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'elevatorId'           => $this->elevatorId,
            'currentFloor'         => $this->currentFloor,
            'currentLoad'          => $this->currentLoad,
            'capacity'             => $this->capacity,
            'assignedPickupCount'  => $this->assignedPickupCount,
            'pickedUpPassengers'   => $this->pickedUpPassengers,
            'droppedOffPassengers' => $this->droppedOffPassengers,
            'direction'            => $this->direction,
            'state'                => $this->state,
            'condition'            => $this->condition,
            'doorState'            => $this->doorState,
            'overloadSavedLoad'    => $this->overloadSavedLoad,
            'plannedStops'         => $this->plannedStops,
        ];
    }
}
