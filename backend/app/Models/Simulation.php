<?php

namespace App\Models;

use App\Modules\Simulation\Zones\ZoneCalculator;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $status
 * @property array<string, mixed> $config_json
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read list<array{elevatorId: string, zoneStart: int, zoneEnd: int}> $zones
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SimulationRun> $runs
 * @property-read int|null $runs_count
 */
class Simulation extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'status',
        'config_json',
    ];

    protected $casts = [
        'config_json' => 'array',
    ];

    /** @var list<string> */
    protected $appends = ['zones'];

    public function runs(): HasMany
    {
        return $this->hasMany(SimulationRun::class);
    }

    /**
     * Computed zone ranges for each elevator shaft, derived from config_json.
     * Single source of truth lives in ZoneCalculator — frontend reads this
     * instead of duplicating the algorithm.
     *
     * @return list<array{elevatorId: string, zoneStart: int, zoneEnd: int}>
     */
    public function getZonesAttribute(): array
    {
        $config = $this->config_json ?? [];
        $floors = (int) ($config['floors'] ?? 1);
        $elevatorCount = max(1, (int) ($config['elevators'] ?? 1));

        $zones = [];
        for ($i = 0; $i < $elevatorCount; $i++) {
            [$zoneStart, $zoneEnd] = ZoneCalculator::bounds($i, $elevatorCount, $floors);
            $zones[] = [
                'elevatorId' => 'E' . ($i + 1),
                'zoneStart'  => $zoneStart,
                'zoneEnd'    => $zoneEnd,
            ];
        }

        return $zones;
    }

    /**
     * @return array<string, int|string>
     */
    public static function defaultConfigPayload(): array
    {
        /** @var array<string, int|string> $defaults */
        $defaults = config('simulation.defaults', []);

        return [
            'name' => (string) $defaults['name'],
            'floors' => (int) $defaults['floors'],
            'elevators' => (int) $defaults['elevators'],
            'capacityPerElevator' => (int) $defaults['capacityPerElevator'],
            'doorOpenSeconds' => (int) $defaults['doorOpenSeconds'],
            'emergencyDescentMultiplier' => (int) $defaults['emergencyDescentMultiplier'],
            'mode' => (string) $defaults['mode'],
            'algorithm' => (string) $defaults['algorithm'],
        ];
    }
}
