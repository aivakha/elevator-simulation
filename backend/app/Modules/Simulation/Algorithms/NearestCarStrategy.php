<?php


namespace App\Modules\Simulation\Algorithms;

use App\Modules\Simulation\DTOs\DispatchCandidate;
use App\Modules\Simulation\DTOs\ManualCallDto;

final class NearestCarStrategy implements DispatchStrategyInterface
{
    public function selectCar(array $elevators, ManualCallDto $call): ?DispatchCandidate
    {
        $bestCandidate = null;

        foreach ($elevators as $elevator) {
            if ($elevator->isUnavailableForDispatch()) {
                continue;
            }

            $distanceToPickup = abs($elevator->currentFloor - $call->originFloor);
            $queueLoadPenalty = $elevator->queuedStopCount();
            $priorityScore    = ($distanceToPickup * 100) + ($queueLoadPenalty * 10);

            if ($bestCandidate === null || $priorityScore < $bestCandidate->priorityScore) {
                $bestCandidate = new DispatchCandidate(
                    elevatorId    : $elevator->elevatorId,
                    priorityScore : $priorityScore,
                );
            }
        }

        return $bestCandidate;
    }
}
