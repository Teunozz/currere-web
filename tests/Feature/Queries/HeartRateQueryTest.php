<?php

declare(strict_types=1);

use App\Models\HeartRateSample;
use App\Models\Run;
use App\Models\User;
use App\Queries\HeartRateQuery;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('only returns runs belonging to the given user', function () {
    $otherUser = User::factory()->create();
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(5),
        'avg_heart_rate' => 150,
    ]);
    Run::factory()->for($otherUser)->create([
        'start_time' => now()->subDays(5),
        'avg_heart_rate' => 150,
    ]);

    $result = (new HeartRateQuery($this->user->id))->forPeriod(30);

    expect($result['runs'])->toHaveCount(1);
});

test('only returns runs within the date window', function () {
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(5),
        'avg_heart_rate' => 150,
    ]);
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(45),
        'avg_heart_rate' => 150,
    ]);

    $result = (new HeartRateQuery($this->user->id))->forPeriod(30);

    expect($result['runs'])->toHaveCount(1);
});

test('skips runs with null avg_heart_rate', function () {
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(5),
        'avg_heart_rate' => 150,
    ]);
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(6),
        'avg_heart_rate' => null,
    ]);

    $result = (new HeartRateQuery($this->user->id))->forPeriod(30);

    expect($result['runs'])->toHaveCount(1);
});

test('max_observed_hr falls back to 190 when no samples exist', function () {
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(5),
        'avg_heart_rate' => 150,
    ]);

    $result = (new HeartRateQuery($this->user->id))->forPeriod(30);

    expect($result['max_observed_hr'])->toBe(190);
});

test('returns per-run shape with eager-loaded sample aggregates', function () {
    $run = Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(5),
        'avg_heart_rate' => 150,
        'duration_seconds' => 1800,
        'distance_km' => 5.0,
    ]);
    HeartRateSample::factory()->for($run)->create(['bpm' => 120]);
    HeartRateSample::factory()->for($run)->create(['bpm' => 175]);
    HeartRateSample::factory()->for($run)->create(['bpm' => 200]);

    $result = (new HeartRateQuery($this->user->id))->forPeriod(30);

    expect($result['max_observed_hr'])->toBe(200);
    expect($result['runs'])->toHaveCount(1);

    $entry = $result['runs'][0];
    expect(array_keys($entry))->toEqualCanonicalizing([
        'id', 'date', 'avg_heart_rate', 'duration_seconds', 'distance_km', 'sample_count', 'min_bpm', 'max_bpm',
    ]);
    expect($entry['id'])->toBe($run->id);
    expect($entry['sample_count'])->toBe(3);
    expect($entry['min_bpm'])->toBe(120);
    expect($entry['max_bpm'])->toBe(200);
});
