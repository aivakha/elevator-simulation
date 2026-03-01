<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $simulation_id
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 * @property int $total_ticks
 * @property array<string, mixed>|null $metrics_json
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Simulation $simulation
 */
class SimulationRun extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'simulation_id',
        'started_at',
        'ended_at',
        'total_ticks',
        'metrics_json',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metrics_json' => 'array',
    ];

    public function simulation(): BelongsTo
    {
        return $this->belongsTo(Simulation::class);
    }
}
