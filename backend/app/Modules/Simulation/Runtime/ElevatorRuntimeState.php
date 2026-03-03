<?php

namespace App\Modules\Simulation\Runtime;

use App\Modules\Simulation\Enums\DoorStateEnum;
use App\Modules\Simulation\Enums\ElevatorConditionEnum;
use App\Modules\Simulation\Enums\ElevatorDirectionEnum;
use App\Modules\Simulation\Enums\ElevatorStateEnum;

final class ElevatorRuntimeState
{
    /**
     * @param list<int> $plannedStops
     */
    public function __construct(
        public string $elevatorId,
        public int $shaftNumber,
        public int $currentFloor,
        public int $currentLoad,
        public int $capacity,
        public int $pickedUpPassengers,
        public int $droppedOffPassengers,
        // Stores pre-overload load for manual overload. Null = not manually overloaded
        public ?int $overloadSavedLoad,
        public ElevatorDirectionEnum $direction,
        public ElevatorStateEnum $state,
        public ElevatorConditionEnum $condition,
        public DoorStateEnum $doorState,
        public int $doorTimerTicks,
        public array $plannedStops,
    ) {
    }

    // Serialization

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'elevatorId'           => $this->elevatorId,
            'shaftNumber'          => $this->shaftNumber,
            'currentFloor'         => $this->currentFloor,
            'currentLoad'          => $this->currentLoad,
            'capacity'             => $this->capacity,
            'pickedUpPassengers'   => $this->pickedUpPassengers,
            'droppedOffPassengers' => $this->droppedOffPassengers,
            'overloadSavedLoad'    => $this->overloadSavedLoad,
            'direction'            => $this->direction->value,
            'state'                => $this->state->value,
            'condition'            => $this->condition->value,
            'doorState'            => $this->doorState->value,
            'doorTimerTicks'       => $this->doorTimerTicks,
            'plannedStops'         => $this->plannedStops,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $elevatorId  = (string) $data['elevatorId'];
        $shaftNumber = (int) ($data['shaftNumber'] ?? 0);

        return new self(
            elevatorId:           $elevatorId,
            shaftNumber:          $shaftNumber,
            currentFloor:         (int) $data['currentFloor'],
            currentLoad:          (int) $data['currentLoad'],
            capacity:             (int) $data['capacity'],
            pickedUpPassengers:   (int) ($data['pickedUpPassengers'] ?? 0),
            droppedOffPassengers: (int) ($data['droppedOffPassengers'] ?? 0),
            overloadSavedLoad:    isset($data['overloadSavedLoad']) ? (int) $data['overloadSavedLoad'] : null,
            direction:            ElevatorDirectionEnum::from((string) $data['direction']),
            state:                ElevatorStateEnum::from((string) ($data['state'] ?? 'Idle')),
            condition:            ElevatorConditionEnum::from((string) ($data['condition'] ?? 'Normal')),
            doorState:            DoorStateEnum::from((string) $data['doorState']),
            doorTimerTicks:       (int) ($data['doorTimerTicks'] ?? 0),
            plannedStops:         array_map('intval', $data['plannedStops'] ?? []),
        );
    }

    // Queries

    public function nextStop(): ?int
    {
        return $this->plannedStops[0] ?? null;
    }

    public function isMoving(): bool
    {
        return $this->state === ElevatorStateEnum::Moving;
    }

    /**
     * Elevator cannot accept new dispatch assignments in its current condition
     */
    public function isUnavailableForDispatch(): bool
    {
        return $this->condition !== ElevatorConditionEnum::Normal;
    }

    public function queuedStopCount(): int
    {
        return count($this->plannedStops);
    }

    // Stop list mutations

    public function removeCurrentStop(): void
    {
        array_shift($this->plannedStops);
    }

    public function appendStop(int $floor): void
    {
        if (in_array($floor, $this->plannedStops, true)) {
            return;
        }

        $this->plannedStops[] = $floor;
    }

    // Door mutations

    /**
     * Transition to Open state with optional hold countdown
     */
    public function openDoor(int $holdTicks = 0): void
    {
        $this->doorState      = DoorStateEnum::Open;
        $this->doorTimerTicks = $holdTicks;
    }

    /**
     * Begin the opening
     */
    public function startOpening(): void
    {
        $this->doorState = DoorStateEnum::Opening;
        $this->doorTimerTicks = 0;
    }

    /**
     * Begin the closing
     */
    public function startClosing(): void
    {
        $this->doorState = DoorStateEnum::Closing;
        $this->doorTimerTicks = 0;
    }

    // Passenger mutations

    /**
     * Board a passenger: add weight and increment pickup counter
     */
    public function board(int $weight): void
    {
        $this->currentLoad += $weight;
        $this->pickedUpPassengers++;
    }

    /**
     * Alight a passenger: subtract weight and increment drop-off counter
     */
    public function alight(int $weight): void
    {
        $this->currentLoad = max(0, $this->currentLoad - $weight);
        $this->droppedOffPassengers++;
    }

    // Condition mutations

    /**
     * Enter emergency recall mode
     */
    public function markEmergency(): void
    {
        $this->condition = ElevatorConditionEnum::Emergency;
    }

    /**
     * Exit emergency mode and return to normal idle
     */
    public function clearEmergency(): void
    {
        $this->condition = ElevatorConditionEnum::Normal;
        $this->state     = ElevatorStateEnum::Idle;
        $this->direction = ElevatorDirectionEnum::Idle;
    }

    /**
     * Manually trigger overload: saves current load, sets artificial overload weight
     * overloadSavedLoad !== null distinguishes manual from weight-triggered overload
     */
    public function markOverloaded(): void
    {
        $this->overloadSavedLoad ??= $this->currentLoad;
        $this->currentLoad = $this->capacity + 200;
        $this->condition   = ElevatorConditionEnum::Overloaded;
    }

    /**
     * Clear overload condition and restore the pre-overload load if it was manual
     */
    public function clearOverloaded(): void
    {
        if ($this->overloadSavedLoad !== null) {
            $this->currentLoad       = max(0, $this->overloadSavedLoad);
            $this->overloadSavedLoad = null;
        }

        $this->condition = ElevatorConditionEnum::Normal;
    }

    /**
     * Begin the out-of-service transition: finish current rides, travel to anchor, then park
     */
    public function markPendingOutOfService(): void
    {
        $this->condition         = ElevatorConditionEnum::PendingOutOfService;
        $this->overloadSavedLoad = null;
        $this->plannedStops      = [];
    }

    /**
     * Fully park the elevator as out of service
     */
    public function markOutOfService(): void
    {
        $this->condition         = ElevatorConditionEnum::OutOfService;
        $this->overloadSavedLoad = null;
        $this->currentLoad       = 0;
        $this->plannedStops      = [];
        $this->direction         = ElevatorDirectionEnum::Idle;
        $this->state             = ElevatorStateEnum::Idle;
        $this->doorState         = DoorStateEnum::Closed;
        $this->doorTimerTicks    = 0;
    }

    /**
     * Return elevator to normal service after re-enabling
     */
    public function returnToIdle(): void
    {
        $this->condition         = ElevatorConditionEnum::Normal;
        $this->overloadSavedLoad = null;
        $this->direction         = ElevatorDirectionEnum::Idle;
        $this->state             = ElevatorStateEnum::Idle;
        $this->startClosing();
    }
}
