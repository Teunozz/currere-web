<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Queries\RunsQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FetchRunsTool implements Tool
{
    public function __construct(
        private int $userId,
        private string $timezone = 'UTC',
    ) {}

    public function name(): string
    {
        return 'fetch_runs';
    }

    public function description(): Stringable|string
    {
        return 'Fetch the user\'s runs with optional filters. Supports rolling-window (days) or absolute date range (from/to), plus min/max filters on distance, pace, and average heart rate. Returns an array of runs each containing a `url` for citation.';
    }

    public function handle(Request $request): Stringable|string
    {
        $filters = array_filter(
            [
                'days' => $request['days'] ?? null,
                'from' => $request['from'] ?? null,
                'to' => $request['to'] ?? null,
                'min_distance_km' => $request['min_distance_km'] ?? null,
                'max_distance_km' => $request['max_distance_km'] ?? null,
                'min_pace_seconds_per_km' => $request['min_pace_seconds_per_km'] ?? null,
                'max_pace_seconds_per_km' => $request['max_pace_seconds_per_km'] ?? null,
                'min_avg_heart_rate' => $request['min_avg_heart_rate'] ?? null,
                'max_avg_heart_rate' => $request['max_avg_heart_rate'] ?? null,
            ],
            fn ($value) => $value !== null,
        );

        $runs = (new RunsQuery($this->userId, $this->timezone))->filtered($filters);

        $rows = $runs->map(fn ($run) => [
            'id' => (int) $run->id,
            'start_time' => $run->start_time->copy()->setTimezone($this->timezone)->toIso8601String(),
            'distance_km' => (float) $run->distance_km,
            'duration_seconds' => (int) $run->duration_seconds,
            'steps' => $run->steps !== null ? (int) $run->steps : null,
            'avg_heart_rate' => $run->avg_heart_rate !== null ? (int) $run->avg_heart_rate : null,
            'avg_pace_seconds_per_km' => $run->avg_pace_seconds_per_km !== null ? (int) $run->avg_pace_seconds_per_km : null,
            'url' => route('runs.show', $run->id),
        ])->all();

        return json_encode($rows);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()->min(1)->max(365),
            'from' => $schema->string(),
            'to' => $schema->string(),
            'min_distance_km' => $schema->number(),
            'max_distance_km' => $schema->number(),
            'min_pace_seconds_per_km' => $schema->integer(),
            'max_pace_seconds_per_km' => $schema->integer(),
            'min_avg_heart_rate' => $schema->integer(),
            'max_avg_heart_rate' => $schema->integer(),
        ];
    }
}
