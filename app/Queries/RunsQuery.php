<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Run;
use Illuminate\Database\Eloquent\Collection;

class RunsQuery
{
    public function __construct(private int $userId) {}

    /**
     * @param  array{
     *     days?: int,
     *     from?: string,
     *     to?: string,
     *     min_distance_km?: float|int,
     *     max_distance_km?: float|int,
     *     min_pace_seconds_per_km?: int,
     *     max_pace_seconds_per_km?: int,
     *     min_avg_heart_rate?: int,
     *     max_avg_heart_rate?: int,
     * }  $filters
     * @return Collection<int, Run>
     */
    public function filtered(array $filters = []): Collection
    {
        $query = Run::query()->where('user_id', $this->userId);

        if (isset($filters['from'])) {
            $query->where('start_time', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('start_time', '<=', $filters['to'].' 23:59:59');
        }

        if (! isset($filters['from']) && ! isset($filters['to']) && isset($filters['days'])) {
            $query->where('start_time', '>=', now()->subDays((int) $filters['days']));
        }

        if (isset($filters['min_distance_km'])) {
            $query->where('distance_km', '>=', $filters['min_distance_km']);
        }

        if (isset($filters['max_distance_km'])) {
            $query->where('distance_km', '<=', $filters['max_distance_km']);
        }

        if (isset($filters['min_pace_seconds_per_km'])) {
            $query->where('avg_pace_seconds_per_km', '>=', $filters['min_pace_seconds_per_km']);
        }

        if (isset($filters['max_pace_seconds_per_km'])) {
            $query->where('avg_pace_seconds_per_km', '<=', $filters['max_pace_seconds_per_km']);
        }

        if (isset($filters['min_avg_heart_rate'])) {
            $query->where('avg_heart_rate', '>=', $filters['min_avg_heart_rate']);
        }

        if (isset($filters['max_avg_heart_rate'])) {
            $query->where('avg_heart_rate', '<=', $filters['max_avg_heart_rate']);
        }

        return $query
            ->orderByDesc('start_time')
            ->get(['id', 'start_time', 'distance_km', 'duration_seconds', 'steps', 'avg_heart_rate', 'avg_pace_seconds_per_km']);
    }
}
