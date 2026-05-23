<?php

declare(strict_types=1);

use App\Models\Run;
use App\Models\User;
use App\Queries\RunsQuery;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('only returns runs belonging to the given user', function () {
    $otherUser = User::factory()->create();
    $mine = Run::factory()->for($this->user)->create(['start_time' => now()->subDays(5)]);
    Run::factory()->for($otherUser)->create(['start_time' => now()->subDays(5)]);

    $runs = (new RunsQuery($this->user->id))->filtered(['days' => 30]);

    expect($runs)->toHaveCount(1);
    expect($runs->first()->id)->toBe($mine->id);
});

test('days filter restricts to a rolling window', function () {
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(5)]);
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(45)]);

    $runs = (new RunsQuery($this->user->id))->filtered(['days' => 30]);

    expect($runs)->toHaveCount(1);
});

test('absolute from/to range overrides days fallback', function () {
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(10)]);
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(40)]);
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(100)]);

    $runs = (new RunsQuery($this->user->id))->filtered([
        'from' => now()->subDays(50)->toDateString(),
        'to' => now()->subDays(5)->toDateString(),
    ]);

    expect($runs)->toHaveCount(2);
});

test('no date filter returns all of the user\'s runs', function () {
    Run::factory()->for($this->user)->create(['start_time' => now()->subYears(2)]);
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(5)]);

    $runs = (new RunsQuery($this->user->id))->filtered();

    expect($runs)->toHaveCount(2);
});

test('min/max distance filters apply independently', function () {
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(3), 'distance_km' => 3.0]);
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(4), 'distance_km' => 8.0]);
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(5), 'distance_km' => 20.0]);

    expect((new RunsQuery($this->user->id))->filtered(['min_distance_km' => 5.0]))->toHaveCount(2);
    expect((new RunsQuery($this->user->id))->filtered(['max_distance_km' => 10.0]))->toHaveCount(2);
    expect((new RunsQuery($this->user->id))->filtered([
        'min_distance_km' => 5.0,
        'max_distance_km' => 10.0,
    ]))->toHaveCount(1);
});

test('min/max pace filters apply independently', function () {
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(3), 'avg_pace_seconds_per_km' => 240]);
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(4), 'avg_pace_seconds_per_km' => 360]);
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(5), 'avg_pace_seconds_per_km' => 480]);

    expect((new RunsQuery($this->user->id))->filtered(['min_pace_seconds_per_km' => 300]))->toHaveCount(2);
    expect((new RunsQuery($this->user->id))->filtered(['max_pace_seconds_per_km' => 400]))->toHaveCount(2);
});

test('min/max avg heart rate filters apply independently', function () {
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(3), 'avg_heart_rate' => 130]);
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(4), 'avg_heart_rate' => 160]);
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(5), 'avg_heart_rate' => 180]);

    expect((new RunsQuery($this->user->id))->filtered(['min_avg_heart_rate' => 150]))->toHaveCount(2);
    expect((new RunsQuery($this->user->id))->filtered(['max_avg_heart_rate' => 170]))->toHaveCount(2);
});

test('returns the expected columns ordered by start_time desc', function () {
    $older = Run::factory()->for($this->user)->create(['start_time' => now()->subDays(10)]);
    $newer = Run::factory()->for($this->user)->create(['start_time' => now()->subDays(2)]);

    $runs = (new RunsQuery($this->user->id))->filtered(['days' => 30]);

    expect($runs->first()->id)->toBe($newer->id);
    expect($runs->last()->id)->toBe($older->id);

    $first = $runs->first()->getAttributes();
    expect(array_keys($first))->toEqualCanonicalizing([
        'id', 'start_time', 'distance_km', 'duration_seconds', 'steps', 'avg_heart_rate', 'avg_pace_seconds_per_km',
    ]);
});
