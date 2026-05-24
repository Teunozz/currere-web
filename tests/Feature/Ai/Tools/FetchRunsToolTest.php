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

test('formats start_time in the supplied timezone so dates near midnight UTC roll forward', function () {
    $run = Run::factory()->for($this->user)->create([
        'start_time' => '2026-05-23 23:30:00',
    ]);

    $tool = new FetchRunsTool($this->user->id, 'Europe/Amsterdam');
    $rows = json_decode((string) $tool->handle(new Request(['days' => 30])), true);

    expect($rows)->toHaveCount(1);
    expect($rows[0]['id'])->toBe($run->id);
    expect($rows[0]['start_time'])->toStartWith('2026-05-24T01:30:00+02:00');
});

test('parses from/to filter as user-tz midnight so UTC-late runs are included', function () {
    Run::factory()->for($this->user)->create(['start_time' => '2026-05-23 22:30:00']);
    Run::factory()->for($this->user)->create(['start_time' => '2026-05-22 18:00:00']);

    $tool = new FetchRunsTool($this->user->id, 'Europe/Amsterdam');
    $rows = json_decode((string) $tool->handle(new Request([
        'from' => '2026-05-24',
        'to' => '2026-05-24',
    ])), true);

    expect($rows)->toHaveCount(1);
    expect($rows[0]['start_time'])->toStartWith('2026-05-24T00:30:00+02:00');
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
