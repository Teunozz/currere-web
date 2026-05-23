<?php

declare(strict_types=1);

namespace App\Http\Requests\Runs;

use App\Models\Run;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        $run = $this->route('run');

        return $this->user() !== null
            && $run instanceof Run
            && $run->user_id === $this->user()->id;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return self::baseRules();
    }

    /**
     * Rule set shared with the chat-confirm endpoint so server-stored changes
     * are revalidated before being applied to the model.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function baseRules(): array
    {
        return [
            'start_time' => ['sometimes', 'date', 'before_or_equal:now'],
            'end_time' => ['sometimes', 'date', 'after:start_time'],
            'distance_km' => ['sometimes', 'numeric', 'min:0.001'],
            'duration_seconds' => ['sometimes', 'integer', 'min:1'],
            'steps' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'avg_heart_rate' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:300'],
            'avg_pace_seconds_per_km' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
