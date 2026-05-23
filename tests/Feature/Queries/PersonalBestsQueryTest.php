<?php

declare(strict_types=1);

use App\Models\Run;
use App\Models\User;
use App\Queries\PersonalBestsQuery;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('returns null for every milestone when there are no runs', function () {
    $result = (new PersonalBestsQuery($this->user->id))->forUser();

    expect(array_keys($result))->toEqualCanonicalizing(['1k', '5k', '10k', 'HM', 'M']);
    expect($result['1k'])->toBeNull();
    expect($result['5k'])->toBeNull();
    expect($result['10k'])->toBeNull();
    expect($result['HM'])->toBeNull();
    expect($result['M'])->toBeNull();
});

test('a milestone PR only counts runs that covered at least that distance', function () {
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(5),
        'distance_km' => 3.0,
        'avg_pace_seconds_per_km' => 250,
    ]);

    $result = (new PersonalBestsQuery($this->user->id))->forUser();

    expect($result['1k'])->not->toBeNull();
    expect($result['5k'])->toBeNull();
    expect($result['10k'])->toBeNull();
});

test('selects the run with the lowest avg pace among eligible runs', function () {
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(10),
        'distance_km' => 6.0,
        'avg_pace_seconds_per_km' => 400,
    ]);
    $fastRun = Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(5),
        'distance_km' => 8.0,
        'avg_pace_seconds_per_km' => 300,
    ]);

    $result = (new PersonalBestsQuery($this->user->id))->forUser();

    expect($result['5k']['run_id'])->toBe($fastRun->id);
    expect($result['5k']['avg_pace_seconds_per_km'])->toBe(300);
});

test('predicted_seconds is avg pace times milestone distance', function () {
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(5),
        'distance_km' => 11.0,
        'avg_pace_seconds_per_km' => 300,
    ]);

    $result = (new PersonalBestsQuery($this->user->id))->forUser();

    expect($result['1k']['predicted_seconds'])->toBe(300);
    expect($result['5k']['predicted_seconds'])->toBe(1500);
    expect($result['10k']['predicted_seconds'])->toBe(3000);
});

test('half marathon and marathon use the standard race distances', function () {
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(5),
        'distance_km' => 42.2,
        'avg_pace_seconds_per_km' => 300,
    ]);

    $result = (new PersonalBestsQuery($this->user->id))->forUser();

    expect($result['HM']['predicted_seconds'])->toBe((int) round(300 * 21.0975));
    expect($result['M']['predicted_seconds'])->toBe((int) round(300 * 42.195));
});

test('excludes runs with null avg pace', function () {
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(5),
        'distance_km' => 6.0,
        'avg_pace_seconds_per_km' => null,
    ]);

    $result = (new PersonalBestsQuery($this->user->id))->forUser();

    expect($result['5k'])->toBeNull();
});

test('isolates by user', function () {
    $otherUser = User::factory()->create();

    Run::factory()->for($otherUser)->create([
        'start_time' => now()->subDays(5),
        'distance_km' => 10.0,
        'avg_pace_seconds_per_km' => 200,
    ]);

    $result = (new PersonalBestsQuery($this->user->id))->forUser();

    expect($result['5k'])->toBeNull();
    expect($result['10k'])->toBeNull();
});

test('returned PR rows contain the documented shape', function () {
    Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(5),
        'distance_km' => 5.5,
        'avg_pace_seconds_per_km' => 300,
    ]);

    $result = (new PersonalBestsQuery($this->user->id))->forUser();

    expect(array_keys($result['5k']))->toEqualCanonicalizing([
        'run_id', 'start_time', 'distance_km', 'avg_pace_seconds_per_km', 'predicted_seconds',
    ]);
});
