<?php

namespace App\Modules\Simulation\Overload;

use App\Modules\Simulation\Runtime\SimulationRuntimeState;

final class OverloadControlService
{
    public function activate(SimulationRuntimeState $state, string $elevatorId): bool
    {
        foreach ($state->elevators as $elevator) {
            if ($elevator->elevatorId !== $elevatorId) {
                continue;
            }

            $elevator->markOverloaded();
            return true;
        }

        return false;
    }

    public function clear(SimulationRuntimeState $state, string $elevatorId): bool
    {
        foreach ($state->elevators as $elevator) {
            if ($elevator->elevatorId !== $elevatorId) {
                continue;
            }

            $elevator->clearOverloaded();
            return true;
        }

        return false;
    }
}
