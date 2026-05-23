<?php

declare(strict_types=1);

use App\Models\Run;
use App\Models\User;
use App\Queries\RunStatsQuery;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('only aggregates runs belonging to the given user', function () {
    $otherUser = User::factory()->create();

    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(10),
        'distance_km' => 5.0,
        'duration_seconds' => 1800,
        'avg_pace_seconds_per_km' => 360,
    ]);
    Run::factory()->for($otherUser)->create([
        'start_time' => now()->subDays(10),
        'distance_km' => 999.0,
        'duration_seconds' => 9999,
        'avg_pace_seconds_per_km' => 100,
    ]);

    $stats = (new RunStatsQuery($this->user->id))->forPeriod(90);

    expect($stats['run_count'])->toBe(1);
    expect($stats['total_distance_km'])->toBe(5.0);
});

test('only aggregates runs within the date window', function () {
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(10),
        'distance_km' => 5.0,
    ]);
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(120),
        'distance_km' => 50.0,
    ]);

    $stats = (new RunStatsQuery($this->user->id))->forPeriod(90);

    expect($stats['run_count'])->toBe(1);
    expect($stats['total_distance_km'])->toBe(5.0);
});

test('returns the documented array shape', function () {
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(5),
        'distance_km' => 10.0,
        'duration_seconds' => 3000,
        'avg_pace_seconds_per_km' => 300,
    ]);
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(20),
        'distance_km' => 4.0,
        'duration_seconds' => 1600,
        'avg_pace_seconds_per_km' => 400,
    ]);

    $stats = (new RunStatsQuery($this->user->id))->forPeriod(90);

    expect(array_keys($stats))->toEqualCanonicalizing([
        'period_days',
        'total_distance_km',
        'total_time_seconds',
        'run_count',
        'avg_pace_seconds_per_km',
        'best_pace_seconds_per_km',
        'longest_run_km',
    ]);
    expect($stats['period_days'])->toBe(90);
    expect($stats['total_distance_km'])->toBe(14.0);
    expect($stats['total_time_seconds'])->toBe(4600);
    expect($stats['run_count'])->toBe(2);
    expect($stats['best_pace_seconds_per_km'])->toBe(300);
    expect($stats['longest_run_km'])->toBe(10.0);
});
