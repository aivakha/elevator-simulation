<?php

namespace App\Modules\Simulation\Zones;

final class ZoneCalculator
{
    /**
     * @return array{0:int,1:int}
     */
    public static function bounds(int $shaftNumber, int $numElevators, int $floors): array
    {
        $maxFloor = max(0, $floors - 1);
        $start    = (int) floor(($shaftNumber * $floors) / $numElevators);
        $end      = (int) floor((($shaftNumber + 1) * $floors) / $numElevators) - 1;

        $start    = max(0, min($maxFloor, $start));
        $end      = max($start, min($maxFloor, $end));

        return [$start, $end];
    }

    public static function anchorFloor(int $shaftNumber, int $numElevators, int $floors): int
    {
        [$zoneStart, $zoneEnd] = self::bounds($shaftNumber, $numElevators, $floors);
        return (int) floor(($zoneStart + $zoneEnd) / 2);
    }
}

