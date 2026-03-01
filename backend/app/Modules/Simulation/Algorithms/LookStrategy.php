<?php


namespace App\Modules\Simulation\Algorithms;

use App\Modules\Simulation\DTOs\DispatchCandidate;
use App\Modules\Simulation\DTOs\ManualCallDto;
use App\Modules\Simulation\Enums\ElevatorDirectionEnum;
use App\Modules\Simulation\Runtime\ElevatorRuntimeState;

final class LookStrategy implements DispatchStrategyInterface
{
    public function selectCar(array $elevators, ManualCallDto $call): ?DispatchCandidate
    {
        $bestCandidate = null;

        foreach ($elevators as $elevator) {
            if ($elevator->isUnavailableForDispatch()) {
                continue;
            }

            $distanceToPickup  = abs($elevator->currentFloor - $call->originFloor);
            $turnAroundPenalty = $this->requiresTurnAround($elevator, $call->originFloor) ? 150 : 0;
            $queueLoadPenalty  = $elevator->queuedStopCount() * 20;
            $priorityScore     = ($distanceToPickup * 100) + $turnAroundPenalty + $queueLoadPenalty;

            if ($bestCandidate === null || $priorityScore < $bestCandidate->priorityScore) {
                $bestCandidate = new DispatchCandidate(
                    elevatorId    : $elevator->elevatorId,
                    priorityScore : $priorityScore,
                );
            }
        }

        return $bestCandidate;
    }

    private function requiresTurnAround(ElevatorRuntimeState $elevator, int $originFloor): bool
    {
        if ($elevator->direction === ElevatorDirectionEnum::Idle) {
            return false;
        }

        if ($elevator->direction === ElevatorDirectionEnum::Up && $originFloor < $elevator->currentFloor) {
            return true;
        }

        if ($elevator->direction === ElevatorDirectionEnum::Down && $originFloor > $elevator->currentFloor) {
            return true;
        }

        return false;
    }
}
