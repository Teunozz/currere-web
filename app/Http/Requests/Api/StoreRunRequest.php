<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'start_time' => ['required', 'date', 'before_or_equal:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'distance_km' => ['required', 'numeric', 'min:0.001'],
            'duration_seconds' => ['required', 'integer', 'min:1'],
            'steps' => ['nullable', 'integer', 'min:0'],
            'avg_heart_rate' => ['nullable', 'integer', 'min:0', 'max:300'],
            'avg_pace_seconds_per_km' => ['nullable', 'integer', 'min:0'],
            'heart_rate_samples' => ['nullable', 'array'],
            'heart_rate_samples.*.timestamp' => ['required', 'date'],
            'heart_rate_samples.*.bpm' => ['required', 'integer', 'min:0', 'max:300'],
            'pace_splits' => ['nullable', 'array'],
            'pace_splits.*.kilometer_number' => ['required', 'integer', 'min:1'],
            'pace_splits.*.split_time_seconds' => ['required', 'integer', 'min:0'],
            'pace_splits.*.pace_seconds_per_km' => ['required', 'integer', 'min:0'],
            'pace_splits.*.is_partial' => ['required', 'boolean'],
            'pace_splits.*.partial_distance_km' => ['nullable', 'numeric', 'min:0.001', 'required_if:pace_splits.*.is_partial,true'],
        ];
    }
}
