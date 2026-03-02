<?php

namespace App\Http\Resources\Simulation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin array{status: string}
 */
final class SimulationStatusResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{status: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => (string) $this['status'],
        ];
    }
}

