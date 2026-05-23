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

    $runs = (new RunsQuery($this->user->id))->recent(30);

    expect($runs)->toHaveCount(1);
    expect($runs->first()->id)->toBe($mine->id);
});

test('only returns runs within the date window', function () {
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(5)]);
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(45)]);

    $runs = (new RunsQuery($this->user->id))->recent(30);

    expect($runs)->toHaveCount(1);
});

test('returns the expected columns ordered by start_time desc', function () {
    $older = Run::factory()->for($this->user)->create(['start_time' => now()->subDays(10)]);
    $newer = Run::factory()->for($this->user)->create(['start_time' => now()->subDays(2)]);

    $runs = (new RunsQuery($this->user->id))->recent(30);

    expect($runs->first()->id)->toBe($newer->id);
    expect($runs->last()->id)->toBe($older->id);

    $first = $runs->first()->getAttributes();
    expect(array_keys($first))->toEqualCanonicalizing([
        'id', 'start_time', 'distance_km', 'duration_seconds', 'steps', 'avg_heart_rate', 'avg_pace_seconds_per_km',
    ]);
});
