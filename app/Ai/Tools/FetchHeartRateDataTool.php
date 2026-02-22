<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\HeartRateSample;
use App\Models\Run;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FetchHeartRateDataTool implements Tool
{
    public function __construct(private int $userId) {}

    public function description(): Stringable|string
    {
        return 'Fetch heart rate data across recent runs. Returns per-run average HR, max observed HR, and time-in-zone estimates.';
    }

    public function handle(Request $request): Stringable|string
    {
        $days = $request['days'] ?? 30;

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
            'date' => $run->start_time->toDateString(),
            'avg_heart_rate' => $run->avg_heart_rate,
            'duration_seconds' => $run->duration_seconds,
            'distance_km' => (float) $run->distance_km,
            'sample_count' => $run->heartRateSamples->count(),
            'min_bpm' => $run->heartRateSamples->min('bpm'),
            'max_bpm' => $run->heartRateSamples->max('bpm'),
        ]);

        return json_encode([
            'max_observed_hr' => $maxHr,
            'runs' => $runData,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()->min(1)->max(365),
        ];
    }
}
