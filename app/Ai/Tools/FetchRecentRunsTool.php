<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Run;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FetchRecentRunsTool implements Tool
{
    public function __construct(private int $userId) {}

    public function description(): Stringable|string
    {
        return 'Fetch recent runs for the user. Returns run data including distance, duration, pace, heart rate, and timestamps.';
    }

    public function handle(Request $request): Stringable|string
    {
        $days = $request['days'] ?? 30;

        $runs = Run::query()
            ->where('user_id', $this->userId)
            ->where('start_time', '>=', now()->subDays($days))
            ->orderByDesc('start_time')
            ->get(['id', 'start_time', 'distance_km', 'duration_seconds', 'steps', 'avg_heart_rate', 'avg_pace_seconds_per_km']);

        return $runs->toJson();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()->min(1)->max(365),
        ];
    }
}
