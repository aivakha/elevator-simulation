<?php

namespace App\Modules\Simulation\Requests;

use App\Modules\Simulation\Enums\ElevatorConditionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ElevatorConditionRequest extends FormRequest
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
                    ElevatorConditionEnum::Normal->value,
                    ElevatorConditionEnum::Overloaded->value,
                    ElevatorConditionEnum::PendingOutOfService->value,
                ]),
            ],
        ];
    }
}
