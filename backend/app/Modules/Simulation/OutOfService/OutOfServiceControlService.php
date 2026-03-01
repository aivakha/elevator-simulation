<?php

namespace App\Modules\Simulation\OutOfService;

use App\Modules\Simulation\Enums\CallStatusEnum;
use App\Modules\Simulation\Runtime\SimulationRuntimeState;

final class OutOfServiceControlService
{
    /** Begin the graceful out-of-service transition for an elevator. */
    public function disable(SimulationRuntimeState $state, string $elevatorId): bool
    {
        foreach ($state->elevators as $elevator) {
            if ($elevator->elevatorId !== $elevatorId) {
                continue;
            }

            $elevator->markPendingOutOfService();
            $this->requeueAssignedCalls($state, $elevatorId);
            return true;
        }

        return false;
    }

    /** Restore an out-of-service or pending elevator to normal service. */
    public function enable(SimulationRuntimeState $state, string $elevatorId): bool
    {
        foreach ($state->elevators as $elevator) {
            if ($elevator->elevatorId !== $elevatorId) {
                continue;
            }

            $elevator->returnToIdle();
            return true;
        }

        return false;
    }

    /** Release assigned (not yet boarding) calls so they can be re-dispatched. */
    private function requeueAssignedCalls(SimulationRuntimeState $state, string $elevatorId): void
    {
        foreach ($state->pendingHallCalls as $call) {
            if ($call->assignedElevatorId !== $elevatorId) {
                continue;
            }

            if ($call->status === CallStatusEnum::Assigned) {
                $call->status             = CallStatusEnum::Pending;
                $call->assignedElevatorId = null;
            }
        }
    }
}
