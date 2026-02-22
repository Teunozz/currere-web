<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaceSplit extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'run_id',
        'kilometer_number',
        'split_time_seconds',
        'pace_seconds_per_km',
        'is_partial',
        'partial_distance_km',
    ];

    protected function casts(): array
    {
        return [
            'is_partial' => 'boolean',
            'partial_distance_km' => 'decimal:3',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }
}
