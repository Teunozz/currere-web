<?php

declare(strict_types=1);

use App\Ai\Tools\FetchPersonalBestsTool;
use App\Models\Run;
use App\Models\User;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('returns all five milestone keys', function () {
    $tool = new FetchPersonalBestsTool($this->user->id);
    $response = $tool->handle(new Request([]));

    expect(array_keys(json_decode((string) $response, true)))
        ->toEqualCanonicalizing(['1k', '5k', '10k', 'HM', 'M']);
});

test('populated milestone entries include a url', function () {
    $run = Run::factory()->for($this->user)->create([
        'start_time' => now()->subDays(5),
        'distance_km' => 12.0,
        'avg_pace_seconds_per_km' => 300,
    ]);

    $tool = new FetchPersonalBestsTool($this->user->id);
    $response = $tool->handle(new Request([]));

    $payload = json_decode((string) $response, true);

    expect($payload['10k'])->not->toBeNull();
    expect($payload['10k']['run_id'])->toBe($run->id);
    expect($payload['10k']['url'])->toBe(route('runs.show', $run->id));

    expect($payload['M'])->toBeNull();
});
