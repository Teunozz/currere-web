<?php

declare(strict_types=1);

use App\Models\HeartRateSample;
use App\Models\PaceSplit;
use App\Models\Run;
use App\Models\User;
use App\Queries\RunDetailQuery;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('returns null when the run does not exist', function () {
    $result = (new RunDetailQuery($this->user->id))->forRun(99999);

    expect($result)->toBeNull();
});

test('returns null when the run belongs to another user', function () {
    $otherUser = User::factory()->create();
    $run = Run::factory()->for($otherUser)->create();

    $result = (new RunDetailQuery($this->user->id))->forRun($run->id);

    expect($result)->toBeNull();
});

test('returns the run with eager-loaded heart rate samples and pace splits', function () {
    $run = Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(2),
        'distance_km' => 5.0,
        'duration_seconds' => 1800,
        'avg_heart_rate' => 150,
        'avg_pace_seconds_per_km' => 360,
    ]);

    HeartRateSample::factory()->for($run)->create(['timestamp' => $run->start_time->copy()->addSeconds(60), 'bpm' => 140]);
    HeartRateSample::factory()->for($run)->create(['timestamp' => $run->start_time->copy()->addSeconds(30), 'bpm' => 130]);

    PaceSplit::factory()->for($run)->create(['kilometer_number' => 2, 'split_time_seconds' => 360, 'pace_seconds_per_km' => 360]);
    PaceSplit::factory()->for($run)->create(['kilometer_number' => 1, 'split_time_seconds' => 350, 'pace_seconds_per_km' => 350]);

    $result = (new RunDetailQuery($this->user->id))->forRun($run->id);

    expect($result)->not->toBeNull();
    expect($result['id'])->toBe($run->id);
    expect($result['distance_km'])->toBe(5.0);
    expect($result['avg_heart_rate'])->toBe(150);

    expect($result['heart_rate_samples'])->toHaveCount(2);
    expect($result['heart_rate_samples'][0]['bpm'])->toBe(130);
    expect($result['heart_rate_samples'][1]['bpm'])->toBe(140);

    expect($result['pace_splits'])->toHaveCount(2);
    expect($result['pace_splits'][0]['kilometer_number'])->toBe(1);
    expect($result['pace_splits'][1]['kilometer_number'])->toBe(2);
});

test('returns the documented top-level shape', function () {
    $run = Run::factory()->for($this->user)->create();

    $result = (new RunDetailQuery($this->user->id))->forRun($run->id);

    expect(array_keys($result))->toEqualCanonicalizing([
        'id', 'start_time', 'end_time', 'distance_km', 'duration_seconds', 'steps', 'avg_heart_rate', 'avg_pace_seconds_per_km', 'heart_rate_samples', 'pace_splits',
    ]);
});
