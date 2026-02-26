<?php

declare(strict_types=1);

use App\Models\Run;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-device')->plainTextToken;
});

test('authenticated user gets paginated runs ordered by start_time desc', function () {
    Run::factory()->for($this->user)->count(3)->sequence(
        ['start_time' => '2026-02-20T08:00:00Z'],
        ['start_time' => '2026-02-21T08:00:00Z'],
        ['start_time' => '2026-02-22T08:00:00Z'],
    )->create();

    $response = $this->getJson('/api/v1/runs', [
        'Authorization' => "Bearer {$this->token}",
    ]);

    $response->assertSuccessful();

    $data = $response->json('data');
    expect($data)->toHaveCount(3);

    $startTimes = array_column($data, 'start_time');
    $sorted = $startTimes;
    usort($sorted, fn ($a, $b) => strcmp($b, $a));
    expect($startTimes)->toBe($sorted);
});

test('pagination works with per_page parameter', function () {
    Run::factory()->for($this->user)->count(5)->create();

    $response = $this->getJson('/api/v1/runs?per_page=2', [
        'Authorization' => "Bearer {$this->token}",
    ]);

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2)
        ->and($response->json('meta.total'))->toBe(5)
        ->and($response->json('meta.last_page'))->toBe(3);
});

test('user only sees their own runs', function () {
    Run::factory()->for($this->user)->count(2)->create();

    $otherUser = User::factory()->create();
    Run::factory()->for($otherUser)->count(3)->create();

    $response = $this->getJson('/api/v1/runs', [
        'Authorization' => "Bearer {$this->token}",
    ]);

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2);
});

test('unauthenticated request returns 401', function () {
    $response = $this->getJson('/api/v1/runs');

    $response->assertUnauthorized();
});

test('empty run list returns empty data array', function () {
    $response = $this->getJson('/api/v1/runs', [
        'Authorization' => "Bearer {$this->token}",
    ]);

    $response->assertSuccessful();
    expect($response->json('data'))->toBeEmpty();
});
