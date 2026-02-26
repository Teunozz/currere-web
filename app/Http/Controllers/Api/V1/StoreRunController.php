<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\StoreRunRequest;
use App\Http\Resources\RunResource;
use App\Models\Run;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StoreRunController
{
    public function __invoke(StoreRunRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $startTime = Carbon::parse($validated['start_time']);

        $existing = Run::query()
            ->where('user_id', $user->id)
            ->where('start_time', $startTime)
            ->where('distance_km', $validated['distance_km'])
            ->first();

        if ($existing) {
            return response()->json([
                'data' => [
                    'id' => $existing->id,
                    'start_time' => $existing->start_time->toIso8601String(),
                    'distance_km' => (float) $existing->distance_km,
                    'already_synced' => true,
                ],
            ], 200);
        }

        $run = DB::transaction(function () use ($user, $validated): Run {
            $run = $user->runs()->create([
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'distance_km' => $validated['distance_km'],
                'duration_seconds' => $validated['duration_seconds'],
                'steps' => $validated['steps'] ?? null,
                'avg_heart_rate' => $validated['avg_heart_rate'] ?? null,
                'avg_pace_seconds_per_km' => $validated['avg_pace_seconds_per_km'] ?? null,
            ]);

            if (! empty($validated['heart_rate_samples'])) {
                $run->heartRateSamples()->insert(
                    array_map(fn (array $sample): array => [
                        'run_id' => $run->id,
                        'timestamp' => $sample['timestamp'],
                        'bpm' => $sample['bpm'],
                    ], $validated['heart_rate_samples'])
                );
            }

            if (! empty($validated['pace_splits'])) {
                $run->paceSplits()->insert(
                    array_map(fn (array $split): array => [
                        'run_id' => $run->id,
                        'kilometer_number' => $split['kilometer_number'],
                        'split_time_seconds' => $split['split_time_seconds'],
                        'pace_seconds_per_km' => $split['pace_seconds_per_km'],
                        'is_partial' => $split['is_partial'],
                        'partial_distance_km' => $split['partial_distance_km'] ?? null,
                    ], $validated['pace_splits'])
                );
            }

            return $run;
        });

        return (new RunResource($run))
            ->response()
            ->setStatusCode(201);
    }
}
