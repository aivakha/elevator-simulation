<?php


namespace App\Modules\Simulation\Algorithms;

use App\Modules\Simulation\DTOs\DispatchCandidate;
use App\Modules\Simulation\DTOs\ManualCallDto;
use App\Modules\Simulation\Enums\ElevatorDirectionEnum;
use App\Modules\Simulation\Runtime\ElevatorRuntimeState;

final class ScanStrategy implements DispatchStrategyInterface
{
    public function selectCar(array $elevators, ManualCallDto $call): ?DispatchCandidate
    {
        $bestCandidate = null;

        foreach ($elevators as $elevator) {
            if ($elevator->isUnavailableForDispatch()) {
                continue;
            }

            $distanceToPickup     = abs($elevator->currentFloor - $call->originFloor);
            $isMovingTowardPickup = $this->isMovingTowardPickup($elevator, $call->originFloor);
            $directionPenalty     = $isMovingTowardPickup ? 0 : 200;
            $priorityScore        = ($distanceToPickup * 100) + $directionPenalty;

            if ($bestCandidate === null || $priorityScore < $bestCandidate->priorityScore) {
                $bestCandidate = new DispatchCandidate(
                    elevatorId    : $elevator->elevatorId,
                    priorityScore : $priorityScore,
                );
            }
        }

        return $bestCandidate;
    }

    private function isMovingTowardPickup(ElevatorRuntimeState $elevator, int $originFloor): bool
    {
        if ($elevator->direction === ElevatorDirectionEnum::Idle) {
            return true;
        }

        if ($elevator->direction === ElevatorDirectionEnum::Up) {
            return $originFloor >= $elevator->currentFloor;
        }

        return $originFloor <= $elevator->currentFloor;
    }
}
