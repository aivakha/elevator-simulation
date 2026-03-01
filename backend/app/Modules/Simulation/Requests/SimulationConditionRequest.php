<?php

namespace App\Modules\Simulation\Requests;

use App\Modules\Simulation\Enums\ElevatorConditionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimulationConditionRequest extends FormRequest
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
        return [
            'condition' => [
                'required',
                'string',
                Rule::in([
                    ElevatorConditionEnum::Emergency->value,
                    ElevatorConditionEnum::Normal->value,
                ]),
            ],
        ];
    }
}
