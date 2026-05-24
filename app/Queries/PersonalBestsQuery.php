<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Run;

class PersonalBestsQuery
{
    /**
     * @var array<string, float>
     */
    private const MILESTONES = [
        '1k' => 1.0,
        '5k' => 5.0,
        '10k' => 10.0,
        'HM' => 21.0975,
        'M' => 42.195,
    ];

    public function __construct(
        private int $userId,
        private string $timezone = 'UTC',
    ) {}

    /**
     * @return array<string, array{run_id: int, start_time: string, distance_km: float, avg_pace_seconds_per_km: int, predicted_seconds: int}|null>
     */
    public function forUser(): array
    {
        $results = [];

        foreach (self::MILESTONES as $label => $distanceKm) {
            $run = Run::query()
                ->where('user_id', $this->userId)
                ->where('distance_km', '>=', $distanceKm)
                ->whereNotNull('avg_pace_seconds_per_km')
                ->orderBy('avg_pace_seconds_per_km')
                ->first(['id', 'start_time', 'distance_km', 'avg_pace_seconds_per_km']);

            if ($run === null) {
                $results[$label] = null;

                continue;
            }

            $results[$label] = [
                'run_id' => (int) $run->id,
                'start_time' => $run->start_time->copy()->setTimezone($this->timezone)->toIso8601String(),
                'distance_km' => (float) $run->distance_km,
                'avg_pace_seconds_per_km' => (int) $run->avg_pace_seconds_per_km,
                'predicted_seconds' => (int) round($run->avg_pace_seconds_per_km * $distanceKm),
            ];
        }

        return $results;
    }
}
