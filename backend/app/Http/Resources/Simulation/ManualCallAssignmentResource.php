<?php

namespace App\Http\Resources\Simulation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin array{assigned: bool, message?: string, assignment?: array<string, int|string>}
 */
final class ManualCallAssignmentResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'assigned' => (bool) $this['assigned'],
            'message' => $this['message'] ?? null,
            'assignment' => $this['assignment'] ?? null,
        ];
    }
}

