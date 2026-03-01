<?php

namespace App\Modules\Simulation\Calls;

use App\Modules\Simulation\Enums\HallCallDirectionEnum;
use App\Modules\Simulation\Enums\SimulationModeEnum;
use App\Modules\Simulation\Runtime\HallCallState;
use App\Modules\Simulation\Runtime\SimulationRuntimeState;
use Random\RandomException;

final class ModeTrafficService
{
    /**
     * @throws RandomException
     */
    public function maybeGenerateCall(SimulationRuntimeState $state): ?HallCallState
    {
        if ($state->mode === SimulationModeEnum::Manual) {
            return null;
        }

        $shouldGenerate = $this->shouldGenerateForMode($state->mode);

        if (!$shouldGenerate) {
            return null;
        }

        [$originFloor, $targetFloor] = $this->pickFloors($state->mode, $state->floors);

        return new HallCallState(
            callId          : HallCallState::newId($state->simulationId, $state->tickNumber, 'call'),
            originFloor     : $originFloor,
            targetFloor     : $targetFloor,
            direction       : HallCallDirectionEnum::fromFloors($originFloor, $targetFloor),
            passengerWeight : random_int(120, 280),
        );
    }

    private function shouldGenerateForMode(SimulationModeEnum $mode): bool
    {
        $roll = random_int(1, 100);

        return match ($mode) {
            SimulationModeEnum::MorningPeak => $roll <= 80,
            SimulationModeEnum::EveningPeak => $roll <= 80,
            SimulationModeEnum::Regular => $roll <= 60,
            SimulationModeEnum::Manual => false,
        };
    }

    /**
     * @return array{0:int,1:int}
     * @throws RandomException
     */
    private function pickFloors(SimulationModeEnum $mode, int $floors): array
    {
        $topFloor = $floors - 1;

        if ($mode === SimulationModeEnum::MorningPeak) {
            $originFloor = 0;
            $targetFloor = random_int(1, $topFloor);

            return [$originFloor, $targetFloor];
        }

        if ($mode === SimulationModeEnum::EveningPeak) {
            $originFloor = random_int(1, $topFloor);
            $targetFloor = 0;

            return [$originFloor, $targetFloor];
        }

        $originFloor = random_int(0, $topFloor);
        $targetFloor = random_int(0, $topFloor);

        while ($originFloor === $targetFloor) {
            $targetFloor = random_int(0, $topFloor);
        }

        return [$originFloor, $targetFloor];
    }

}
