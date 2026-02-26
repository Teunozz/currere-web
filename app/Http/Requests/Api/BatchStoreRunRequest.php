<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class BatchStoreRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'runs' => ['required', 'array', 'min:1'],
            'runs.*.start_time' => ['required', 'date', 'before_or_equal:now'],
            'runs.*.end_time' => ['required', 'date', 'after:runs.*.start_time'],
            'runs.*.distance_km' => ['required', 'numeric', 'min:0.001'],
            'runs.*.duration_seconds' => ['required', 'integer', 'min:1'],
            'runs.*.steps' => ['nullable', 'integer', 'min:0'],
            'runs.*.avg_heart_rate' => ['nullable', 'integer', 'min:0', 'max:300'],
            'runs.*.avg_pace_seconds_per_km' => ['nullable', 'integer', 'min:0'],
            'runs.*.heart_rate_samples' => ['nullable', 'array'],
            'runs.*.heart_rate_samples.*.timestamp' => ['required', 'date'],
            'runs.*.heart_rate_samples.*.bpm' => ['required', 'integer', 'min:0', 'max:300'],
            'runs.*.pace_splits' => ['nullable', 'array'],
            'runs.*.pace_splits.*.kilometer_number' => ['required', 'integer', 'min:1'],
            'runs.*.pace_splits.*.split_time_seconds' => ['required', 'integer', 'min:0'],
            'runs.*.pace_splits.*.pace_seconds_per_km' => ['required', 'integer', 'min:0'],
            'runs.*.pace_splits.*.is_partial' => ['required', 'boolean'],
            'runs.*.pace_splits.*.partial_distance_km' => ['nullable', 'numeric', 'min:0.001', 'required_if:runs.*.pace_splits.*.is_partial,true'],
        ];
    }
}
