<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\HeartRateSample */
class HeartRateSampleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'timestamp' => $this->timestamp->toIso8601String(),
            'bpm' => $this->bpm,
        ];
    }
}
