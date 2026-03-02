<?php

namespace App\Http\Resources\Simulation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin array{scope: string, elevatorId: string, condition: string}
 */
final class ElevatorConditionResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{scope: string, elevatorId: string, condition: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'scope' => (string) $this['scope'],
            'elevatorId' => (string) $this['elevatorId'],
            'condition' => (string) $this['condition'],
        ];
    }
}

