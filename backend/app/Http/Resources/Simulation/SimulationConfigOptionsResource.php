<?php

namespace App\Http\Resources\Simulation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin array{defaults: array<string, mixed>, limits: array<string, mixed>}
 */
final class SimulationConfigOptionsResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{defaults: array<string, mixed>, limits: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'defaults' => $this['defaults'],
            'limits' => $this['limits'],
        ];
    }
}

