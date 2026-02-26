<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Run extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_time',
        'end_time',
        'distance_km',
        'duration_seconds',
        'steps',
        'avg_heart_rate',
        'avg_pace_seconds_per_km',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'distance_km' => 'decimal:3',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function heartRateSamples(): HasMany
    {
        return $this->hasMany(HeartRateSample::class);
    }

    public function paceSplits(): HasMany
    {
        return $this->hasMany(PaceSplit::class);
    }
}
