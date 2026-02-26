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
        ];
    }
}
