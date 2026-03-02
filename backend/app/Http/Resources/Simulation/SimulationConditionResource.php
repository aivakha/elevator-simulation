<?php

namespace App\Http\Resources\Simulation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin array{scope: string, condition: string, isEmergencyMode: bool}
 */
final class SimulationConditionResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{scope: string, condition: string, isEmergencyMode: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'scope' => (string) $this['scope'],
            'condition' => (string) $this['condition'],
            'isEmergencyMode' => (bool) $this['isEmergencyMode'],
        ];
    }
}

