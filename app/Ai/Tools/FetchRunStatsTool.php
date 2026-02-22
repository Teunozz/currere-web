<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Run;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FetchRunStatsTool implements Tool
{
    public function __construct(private int $userId) {}

    public function description(): Stringable|string
    {
        return 'Fetch aggregated run statistics for the user over a given period. Returns total distance, total time, run count, average pace, and best pace.';
    }

    public function handle(Request $request): Stringable|string
    {
        $days = $request['days'] ?? 90;

        $query = Run::query()
            ->where('user_id', $this->userId)
            ->where('start_time', '>=', now()->subDays($days));

        $stats = [
            'period_days' => $days,
            'total_distance_km' => (float) $query->sum('distance_km'),
            'total_time_seconds' => (int) $query->sum('duration_seconds'),
            'run_count' => $query->count(),
            'avg_pace_seconds_per_km' => (int) $query->avg('avg_pace_seconds_per_km'),
            'best_pace_seconds_per_km' => (int) $query->min('avg_pace_seconds_per_km'),
            'longest_run_km' => (float) $query->max('distance_km'),
        ];

        return json_encode($stats);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()->min(1)->max(365),
        ];
    }
}
