<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\HeartRateSample;
use App\Models\Run;

class HeartRateQuery
{
    public function __construct(
        private int $userId,
        private string $timezone = 'UTC',
    ) {}

    /**
     * @return array{max_observed_hr: int, runs: array<int, array{id: int, date: string, avg_heart_rate: int|null, duration_seconds: int, distance_km: float, sample_count: int, min_bpm: int|null, max_bpm: int|null}>}
     */
    public function forPeriod(int $days = 30): array
    {
        $runs = Run::query()
            ->where('user_id', $this->userId)
            ->where('start_time', '>=', now()->subDays($days))
            ->whereNotNull('avg_heart_rate')
            ->with(['heartRateSamples' => fn ($q) => $q->orderBy('timestamp')])
            ->orderByDesc('start_time')
            ->get();

        $maxHr = HeartRateSample::query()
            ->whereIn('run_id', $runs->pluck('id'))
            ->max('bpm') ?? 190;

        $runData = $runs->map(fn (Run $run) => [
            'id' => $run->id,
            'date' => $run->start_time->copy()->setTimezone($this->timezone)->toDateString(),
            'avg_heart_rate' => $run->avg_heart_rate,
            'duration_seconds' => $run->duration_seconds,
            'distance_km' => (float) $run->distance_km,
            'sample_count' => $run->heartRateSamples->count(),
            'min_bpm' => $run->heartRateSamples->min('bpm'),
            'max_bpm' => $run->heartRateSamples->max('bpm'),
        ])->all();

        return [
            'max_observed_hr' => (int) $maxHr,
            'runs' => $runData,
        ];
    }
}
