<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Run */
class RunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'start_time' => $this->start_time->toIso8601String(),
            'end_time' => $this->end_time->toIso8601String(),
            'distance_km' => (float) $this->distance_km,
            'duration_seconds' => $this->duration_seconds,
            'steps' => $this->steps,
            'avg_heart_rate' => $this->avg_heart_rate,
            'avg_pace_seconds_per_km' => $this->avg_pace_seconds_per_km,
            'created_at' => $this->created_at->toIso8601String(),
            'heart_rate_samples' => $this->whenLoaded('heartRateSamples', fn () => $this->heartRateSamples->map(fn ($sample) => [
                'timestamp' => $sample->timestamp->toIso8601String(),
                'bpm' => $sample->bpm,
            ])),
            'pace_splits' => $this->whenLoaded('paceSplits', fn () => $this->paceSplits->map(fn ($split) => [
                'kilometer_number' => $split->kilometer_number,
                'split_time_seconds' => $split->split_time_seconds,
                'pace_seconds_per_km' => (float) $split->pace_seconds_per_km,
                'is_partial' => $split->is_partial,
                'partial_distance_km' => $split->partial_distance_km ? (float) $split->partial_distance_km : null,
            ])),
        ];
    }
}
