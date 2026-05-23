<?php

declare(strict_types=1);

use App\Ai\Tools\FetchRunDetailTool;
use App\Models\HeartRateSample;
use App\Models\PaceSplit;
use App\Models\Run;
use App\Models\User;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('returns the run detail with nested samples, splits, and a url', function () {
    $run = Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(2),
        'distance_km' => 5.0,
        'avg_heart_rate' => 150,
    ]);
    HeartRateSample::factory()->for($run)->create(['bpm' => 140]);
    PaceSplit::factory()->for($run)->create(['kilometer_number' => 1, 'pace_seconds_per_km' => 300]);

    $tool = new FetchRunDetailTool($this->user->id);
    $response = $tool->handle(new Request(['run_id' => $run->id]));

    $payload = json_decode((string) $response, true);

    expect($payload['id'])->toBe($run->id);
    expect($payload['url'])->toBe(route('runs.show', $run->id));
    expect($payload['heart_rate_samples'])->toHaveCount(1);
    expect($payload['pace_splits'])->toHaveCount(1);
});

test('returns an error payload for an unknown run id', function () {
    $tool = new FetchRunDetailTool($this->user->id);
    $response = $tool->handle(new Request(['run_id' => 99999]));

    expect(json_decode((string) $response, true))->toBe(['error' => 'Run not found']);
});

test('returns an error payload for another user\'s run', function () {
    $otherUser = User::factory()->create();
    $run = Run::factory()->for($otherUser)->create();

    $tool = new FetchRunDetailTool($this->user->id);
    $response = $tool->handle(new Request(['run_id' => $run->id]));

    expect(json_decode((string) $response, true))->toBe(['error' => 'Run not found']);
});
