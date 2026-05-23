<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Run;

class RunStatsQuery
{
    public function __construct(private int $userId) {}

    /**
     * @return array{period_days: int, total_distance_km: float, total_time_seconds: int, run_count: int, avg_pace_seconds_per_km: int, best_pace_seconds_per_km: int, longest_run_km: float}
     */
    public function forPeriod(int $days = 90): array
    {
        $query = Run::query()
            ->where('user_id', $this->userId)
            ->where('start_time', '>=', now()->subDays($days));

        return [
            'period_days' => $days,
            'total_distance_km' => (float) $query->sum('distance_km'),
            'total_time_seconds' => (int) $query->sum('duration_seconds'),
            'run_count' => $query->count(),
            'avg_pace_seconds_per_km' => (int) $query->avg('avg_pace_seconds_per_km'),
            'best_pace_seconds_per_km' => (int) $query->min('avg_pace_seconds_per_km'),
            'longest_run_km' => (float) $query->max('distance_km'),
        ];
    }
}
