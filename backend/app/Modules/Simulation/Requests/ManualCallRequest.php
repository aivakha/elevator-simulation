<?php

namespace App\Modules\Simulation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $limits = config('simulation.limits', []);

        return [
            'originFloor' => ['required', 'integer', 'min:0'],
            'destinationFloor' => ['required', 'integer', 'min:0', 'different:originFloor'],
            'passengerWeight' => ['required', 'integer', 'min:1', 'max:' . (int) $limits['maxCapacityPerElevator']],
        ];
    }
}

