<?php

namespace App\Modules\Simulation\Requests;

use App\Modules\Simulation\Enums\DispatchAlgorithmEnum;
use App\Modules\Simulation\Enums\SimulationModeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimulationConfigRequest extends FormRequest
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
        /** @var array<string, int> $limits */
        $limits = config('simulation.limits', []);

        $floorRules = [
            'sometimes',
            'integer',
            'min:' . $limits['minFloors'],
            'max:' . $limits['maxFloors'],
        ];

        $elevatorRules = [
            'sometimes',
            'integer',
            'min:' . $limits['minElevators'],
            'max:' . $limits['maxElevators'],
        ];

        $capacityRules = [
            'sometimes',
            'integer',
            'min:' . $limits['minCapacityPerElevator'],
            'max:' . $limits['maxCapacityPerElevator'],
        ];

        $doorOpenRules = [
            'sometimes',
            'integer',
            'min:' . $limits['minDoorOpenSeconds'],
            'max:' . $limits['maxDoorOpenSeconds'],
        ];

        $modeValues = array_map(
            static fn (SimulationModeEnum $mode): string => $mode->value,
            SimulationModeEnum::cases(),
        );

        $algorithmValues = array_map(
            static fn (DispatchAlgorithmEnum $algorithm): string => $algorithm->value,
            DispatchAlgorithmEnum::cases(),
        );

        return [
            'id'                  => ['sometimes', 'string', 'max:64'],
            'name'                => ['sometimes', 'string', 'max:120'],
            'floors'               => $floorRules,
            'elevators'           => $elevatorRules,
            'capacityPerElevator' => $capacityRules,
            'doorOpenSeconds'     => $doorOpenRules,
            'mode'                => ['sometimes', Rule::in($modeValues)],
            'algorithm'           => ['sometimes', Rule::in($algorithmValues)],
        ];
    }
}
