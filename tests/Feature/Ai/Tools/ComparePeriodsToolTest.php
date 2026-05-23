<?php

declare(strict_types=1);

use App\Ai\Tools\ComparePeriodsTool;
use App\Models\Run;
use App\Models\User;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('returns period_a and period_b aggregates', function () {
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(7),
        'distance_km' => 10.0,
        'duration_seconds' => 3000,
        'avg_pace_seconds_per_km' => 300,
    ]);
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(40),
        'distance_km' => 4.0,
        'duration_seconds' => 1600,
        'avg_pace_seconds_per_km' => 400,
    ]);

    $tool = new ComparePeriodsTool($this->user->id);
    $response = $tool->handle(new Request([
        'from_a' => now()->subDays(14)->toDateString(),
        'to_a' => now()->toDateString(),
        'from_b' => now()->subDays(60)->toDateString(),
        'to_b' => now()->subDays(20)->toDateString(),
    ]));

    $payload = json_decode((string) $response, true);

    expect($payload)->toHaveKeys(['period_a', 'period_b']);
    expect($payload['period_a']['run_count'])->toBe(1);
    expect((float) $payload['period_a']['total_distance_km'])->toBe(10.0);
    expect($payload['period_b']['run_count'])->toBe(1);
    expect((float) $payload['period_b']['total_distance_km'])->toBe(4.0);
});
