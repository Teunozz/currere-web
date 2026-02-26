<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\BatchStoreRunRequest;
use App\Models\Run;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BatchStoreRunController
{
    public function __invoke(BatchStoreRunRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $created = 0;
        $skipped = 0;
        $results = [];

        foreach ($validated['runs'] as $index => $runData) {
            $startTime = Carbon::parse($runData['start_time']);

            $existing = Run::query()
                ->where('user_id', $user->id)
                ->where('start_time', $startTime)
                ->where('distance_km', $runData['distance_km'])
                ->first();

            if ($existing) {
                $skipped++;
                $results[] = [
                    'index' => $index,
                    'status' => 'skipped',
                    'id' => $existing->id,
                    'already_synced' => true,
                ];

                continue;
            }

            $run = DB::transaction(function () use ($user, $runData): Run {
                $run = $user->runs()->create([
                    'start_time' => $runData['start_time'],
                    'end_time' => $runData['end_time'],
                    'distance_km' => $runData['distance_km'],
                    'duration_seconds' => $runData['duration_seconds'],
                    'steps' => $runData['steps'] ?? null,
                    'avg_heart_rate' => $runData['avg_heart_rate'] ?? null,
                    'avg_pace_seconds_per_km' => $runData['avg_pace_seconds_per_km'] ?? null,
                ]);

                if (! empty($runData['heart_rate_samples'])) {
                    $run->heartRateSamples()->insert(
                        array_map(fn (array $sample): array => [
                            'run_id' => $run->id,
                            'timestamp' => $sample['timestamp'],
                            'bpm' => $sample['bpm'],
                        ], $runData['heart_rate_samples'])
                    );
                }

                if (! empty($runData['pace_splits'])) {
                    $run->paceSplits()->insert(
                        array_map(fn (array $split): array => [
                            'run_id' => $run->id,
                            'kilometer_number' => $split['kilometer_number'],
                            'split_time_seconds' => $split['split_time_seconds'],
                            'pace_seconds_per_km' => $split['pace_seconds_per_km'],
                            'is_partial' => $split['is_partial'],
                            'partial_distance_km' => $split['partial_distance_km'] ?? null,
                        ], $runData['pace_splits'])
                    );
                }

                return $run;
            });

            $created++;
            $results[] = [
                'index' => $index,
                'status' => 'created',
                'id' => $run->id,
            ];
        }

        return response()->json([
            'data' => [
                'created' => $created,
                'skipped' => $skipped,
                'results' => $results,
            ],
        ], 201);
    }
}
