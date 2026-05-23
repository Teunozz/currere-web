<?php

declare(strict_types=1);

namespace App\Services\Runs;

use App\Models\Run;

class UpdateRunAction
{
    /** @var list<string> */
    public const EDITABLE_FIELDS = [
        'start_time',
        'end_time',
        'distance_km',
        'duration_seconds',
        'steps',
        'avg_heart_rate',
        'avg_pace_seconds_per_km',
    ];

    /**
     * Apply the supplied changes to the run and persist.
     *
     * @param  array<string, mixed>  $changes
     */
    public function execute(Run $run, array $changes): Run
    {
        $allowed = array_intersect_key($changes, array_flip(self::EDITABLE_FIELDS));

        $run->fill($allowed)->save();

        return $run->refresh();
    }
}
