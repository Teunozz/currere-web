<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Run;
use Illuminate\Database\Eloquent\Collection;

class RunsQuery
{
    public function __construct(private int $userId) {}

    /**
     * @return Collection<int, Run>
     */
    public function recent(int $days = 30): Collection
    {
        return Run::query()
            ->where('user_id', $this->userId)
            ->where('start_time', '>=', now()->subDays($days))
            ->orderByDesc('start_time')
            ->get(['id', 'start_time', 'distance_km', 'duration_seconds', 'steps', 'avg_heart_rate', 'avg_pace_seconds_per_km']);
    }
}
