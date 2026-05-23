<?php

declare(strict_types=1);

namespace App\Queries;

class ComparePeriodsQuery
{
    public function __construct(private int $userId) {}

    /**
     * @return array{
     *     period_a: array{from: string, to: string, total_distance_km: float, total_time_seconds: int, run_count: int, avg_pace_seconds_per_km: int, best_pace_seconds_per_km: int, longest_run_km: float},
     *     period_b: array{from: string, to: string, total_distance_km: float, total_time_seconds: int, run_count: int, avg_pace_seconds_per_km: int, best_pace_seconds_per_km: int, longest_run_km: float},
     * }
     */
    public function compare(string $fromA, string $toA, string $fromB, string $toB): array
    {
        $stats = new RunStatsQuery($this->userId);

        return [
            'period_a' => $stats->forDateRange($fromA, $toA),
            'period_b' => $stats->forDateRange($fromB, $toB),
        ];
    }
}
