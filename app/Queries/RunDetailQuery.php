<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Run;

class RunDetailQuery
{
    public function __construct(
        private int $userId,
        private string $timezone = 'UTC',
    ) {}

    /**
     * @return array{
     *     id: int,
     *     start_time: string,
     *     end_time: string,
     *     distance_km: float,
     *     duration_seconds: int,
     *     steps: int|null,
     *     avg_heart_rate: int|null,
     *     avg_pace_seconds_per_km: int|null,
     *     heart_rate_samples: array<int, array{timestamp: string, bpm: int}>,
     *     pace_splits: array<int, array{kilometer_number: int, split_time_seconds: int, pace_seconds_per_km: int, is_partial: bool, partial_distance_km: float|null}>,
     * }|null
     */
    public function forRun(int $runId): ?array
    {
        $run = Run::query()
            ->where('user_id', $this->userId)
            ->where('id', $runId)
            ->with([
                'heartRateSamples' => fn ($q) => $q->orderBy('timestamp'),
                'paceSplits' => fn ($q) => $q->orderBy('kilometer_number'),
            ])
            ->first();

        if ($run === null) {
            return null;
        }

        return [
            'id' => $run->id,
            'start_time' => $run->start_time->copy()->setTimezone($this->timezone)->toIso8601String(),
            'end_time' => $run->end_time->copy()->setTimezone($this->timezone)->toIso8601String(),
            'distance_km' => (float) $run->distance_km,
            'duration_seconds' => (int) $run->duration_seconds,
            'steps' => $run->steps !== null ? (int) $run->steps : null,
            'avg_heart_rate' => $run->avg_heart_rate !== null ? (int) $run->avg_heart_rate : null,
            'avg_pace_seconds_per_km' => $run->avg_pace_seconds_per_km !== null ? (int) $run->avg_pace_seconds_per_km : null,
            'heart_rate_samples' => $run->heartRateSamples->map(fn ($sample) => [
                'timestamp' => $sample->timestamp->copy()->setTimezone($this->timezone)->toIso8601String(),
                'bpm' => (int) $sample->bpm,
            ])->all(),
            'pace_splits' => $run->paceSplits->map(fn ($split) => [
                'kilometer_number' => (int) $split->kilometer_number,
                'split_time_seconds' => (int) $split->split_time_seconds,
                'pace_seconds_per_km' => (int) $split->pace_seconds_per_km,
                'is_partial' => (bool) $split->is_partial,
                'partial_distance_km' => $split->partial_distance_km !== null ? (float) $split->partial_distance_km : null,
            ])->all(),
        ];
    }
}
