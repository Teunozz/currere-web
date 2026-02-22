<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PaceSplit */
class PaceSplitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kilometer_number' => $this->kilometer_number,
            'split_time_seconds' => $this->split_time_seconds,
            'pace_seconds_per_km' => $this->pace_seconds_per_km,
            'is_partial' => $this->is_partial,
            'partial_distance_km' => $this->partial_distance_km ? (float) $this->partial_distance_km : null,
        ];
    }
}
