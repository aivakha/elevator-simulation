<?php

namespace App\Modules\Simulation\DTOs;

use App\Modules\Simulation\Enums\CallStatusEnum;
use App\Modules\Simulation\Runtime\SimulationRuntimeState;

final readonly class SimulationSnapshotDto
{
    /**
     * @param list<array<string, mixed>> $elevators
     * @param list<array<string, mixed>> $pendingHallCalls
     */
    public function __construct(
        public string $simulationId,
        public int $tickNumber,
        public string $mode,
        public string $algorithm,
        public bool $isEmergencyMode,
        public int $tickIntervalMs,
        public int $floorTravelSeconds,
        public int $maxPendingCalls,
        public int $waitingPassengers,
        public int $pickedUpPassengers,
        public int $droppedOffPassengers,
        public int $totalPassengers,
        public array $elevators,
        public array $pendingHallCalls,
    ) {
    }

    public static function fromState(SimulationRuntimeState $state): self
    {
        $assignedPickupCountsByElevator = [];
        $waitingPassengers = 0;
        $pendingHallCalls = [];

        foreach ($state->pendingHallCalls as $call) {
            if ($call->status === CallStatusEnum::Served) {
                continue;
            }

            if ($call->status === CallStatusEnum::Assigned && $call->assignedElevatorId !== null) {
                $assignedPickupCountsByElevator[$call->assignedElevatorId] =
                    ($assignedPickupCountsByElevator[$call->assignedElevatorId] ?? 0) + 1;
            }

            if ($call->status === CallStatusEnum::Pending) {
                $waitingPassengers++;
            }

            $pendingHallCalls[] = HallCallSnapshotDto::fromRuntimeState($call)->toArray();
        }

        $elevators = array_map(
            fn($e) => ElevatorSnapshotDto::fromRuntimeState($e, $assignedPickupCountsByElevator[$e->elevatorId] ?? 0)->toArray(),
            $state->elevators,
        );

        return new self(
            simulationId:        $state->simulationId,
            tickNumber:          $state->tickNumber,
            mode:                $state->mode->value,
            algorithm:           $state->algorithm->value,
            isEmergencyMode:     $state->isEmergencyMode,
            tickIntervalMs:      $state->tickIntervalMs,
            floorTravelSeconds:  $state->floorTravelSeconds,
            maxPendingCalls:     $state->maxPendingCalls,
            waitingPassengers:   $waitingPassengers,
            pickedUpPassengers:  $state->pickedUpPassengers,
            droppedOffPassengers: $state->droppedOffPassengers,
            totalPassengers:     $waitingPassengers + $state->pickedUpPassengers + $state->droppedOffPassengers,
            elevators:           $elevators,
            pendingHallCalls:    $pendingHallCalls,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'simulationId'        => $this->simulationId,
            'tickNumber'          => $this->tickNumber,
            'mode'                => $this->mode,
            'algorithm'           => $this->algorithm,
            'isEmergencyMode'     => $this->isEmergencyMode,
            'tickIntervalMs'      => $this->tickIntervalMs,
            'floorTravelSeconds'  => $this->floorTravelSeconds,
            'maxPendingCalls'     => $this->maxPendingCalls,
            'waitingPassengers'   => $this->waitingPassengers,
            'pickedUpPassengers'  => $this->pickedUpPassengers,
            'droppedOffPassengers' => $this->droppedOffPassengers,
            'totalPassengers'     => $this->totalPassengers,
            'elevators'           => $this->elevators,
            'pendingHallCalls'    => $this->pendingHallCalls,
        ];
    }
}
