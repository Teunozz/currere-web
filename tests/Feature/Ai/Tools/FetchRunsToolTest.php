<?php

declare(strict_types=1);

use App\Ai\Tools\FetchRunsTool;
use App\Models\Run;
use App\Models\User;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('returns runs as JSON with a url field on each row', function () {
    $run = Run::factory()->for($this->user)->create(['start_time' => now()->subDays(3)]);

    $tool = new FetchRunsTool($this->user->id);
    $response = $tool->handle(new Request(['days' => 30]));

    $rows = json_decode((string) $response, true);

    expect($rows)->toHaveCount(1);
    expect($rows[0]['id'])->toBe($run->id);
    expect($rows[0]['url'])->toBe(route('runs.show', $run->id));
});

test('passes the days filter through to the query', function () {
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(5)]);
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(45)]);

    $tool = new FetchRunsTool($this->user->id);
    $response = $tool->handle(new Request(['days' => 7]));

    expect(json_decode((string) $response, true))->toHaveCount(1);
});

test('passes from/to filter through to the query', function () {
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(5)]);
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(60)]);

    $tool = new FetchRunsTool($this->user->id);
    $response = $tool->handle(new Request([
        'from' => now()->subDays(30)->toDateString(),
        'to' => now()->toDateString(),
    ]));

    expect(json_decode((string) $response, true))->toHaveCount(1);
});

test('passes distance filters through to the query', function () {
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(3), 'distance_km' => 2.0]);
    Run::factory()->for($this->user)->create(['start_time' => now()->subDays(5), 'distance_km' => 12.0]);

    $tool = new FetchRunsTool($this->user->id);
    $response = $tool->handle(new Request(['min_distance_km' => 10]));

    expect(json_decode((string) $response, true))->toHaveCount(1);
});

test('returns an empty array when there are no runs', function () {
    $tool = new FetchRunsTool($this->user->id);
    $response = $tool->handle(new Request([]));

    expect(json_decode((string) $response, true))->toBe([]);
});
