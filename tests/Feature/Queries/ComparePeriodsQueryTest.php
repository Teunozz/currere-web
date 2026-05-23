<?php

declare(strict_types=1);

use App\Models\Run;
use App\Models\User;
use App\Queries\ComparePeriodsQuery;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('aggregates two periods independently', function () {
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

    $result = (new ComparePeriodsQuery($this->user->id))->compare(
        now()->subDays(14)->toDateString(),
        now()->toDateString(),
        now()->subDays(50)->toDateString(),
        now()->subDays(20)->toDateString(),
    );

    expect($result)->toHaveKeys(['period_a', 'period_b']);
    expect($result['period_a']['run_count'])->toBe(1);
    expect($result['period_a']['total_distance_km'])->toBe(10.0);
    expect($result['period_b']['run_count'])->toBe(1);
    expect($result['period_b']['total_distance_km'])->toBe(4.0);
});

test('isolates by user across both periods', function () {
    $otherUser = User::factory()->create();

    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(7),
        'distance_km' => 5.0,
    ]);
    Run::factory()->for($otherUser)->create([
        'start_time' => now()->subDays(7),
        'distance_km' => 999.0,
    ]);

    $result = (new ComparePeriodsQuery($this->user->id))->compare(
        now()->subDays(14)->toDateString(),
        now()->toDateString(),
        now()->subDays(60)->toDateString(),
        now()->subDays(30)->toDateString(),
    );

    expect($result['period_a']['run_count'])->toBe(1);
    expect($result['period_a']['total_distance_km'])->toBe(5.0);
});

test('empty period returns a zero-shape aggregate', function () {
    $result = (new ComparePeriodsQuery($this->user->id))->compare(
        now()->subDays(14)->toDateString(),
        now()->toDateString(),
        now()->subDays(60)->toDateString(),
        now()->subDays(30)->toDateString(),
    );

    expect($result['period_a']['run_count'])->toBe(0);
    expect($result['period_a']['total_distance_km'])->toBe(0.0);
    expect($result['period_a']['best_pace_seconds_per_km'])->toBe(0);
});
