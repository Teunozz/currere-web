<?php

declare(strict_types=1);

use App\Models\Run;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-device')->plainTextToken;
});

test('authenticated user can create a run with heart rate samples and pace splits', function () {
    $payload = validRunPayload();

    $response = $this->postJson('/api/v1/runs', $payload, authHeader($this->token));

    $response->assertCreated()
        ->assertJsonPath('data.distance_km', 10.5)
        ->assertJsonPath('data.duration_seconds', 2700)
        ->assertJsonPath('data.steps', 12500)
        ->assertJsonPath('data.avg_heart_rate', 155);

    $this->assertDatabaseCount('runs', 1);
    $this->assertDatabaseCount('heart_rate_samples', 2);
    $this->assertDatabaseCount('pace_splits', 2);

    $run = Run::first();
    expect($run->user_id)->toBe($this->user->id)
        ->and($run->heartRateSamples)->toHaveCount(2)
        ->and($run->paceSplits)->toHaveCount(2);
});

test('duplicate run returns 200 with already_synced flag', function () {
    $payload = validRunPayload();

    $this->postJson('/api/v1/runs', $payload, authHeader($this->token))
        ->assertCreated();

    $response = $this->postJson('/api/v1/runs', $payload, authHeader($this->token));

    $response->assertOk()
        ->assertJsonPath('data.already_synced', true)
        ->assertJsonPath('data.distance_km', 10.5);

    $this->assertDatabaseCount('runs', 1);
});

test('invalid data returns 422', function () {
    $response = $this->postJson('/api/v1/runs', [
        'distance_km' => 10.5,
    ], authHeader($this->token));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['start_time', 'end_time', 'duration_seconds']);
});

test('invalid nested heart rate sample returns 422', function () {
    $payload = validRunPayload();
    $payload['heart_rate_samples'] = [
        ['timestamp' => '2026-02-22T08:30:00Z'],
    ];

    $response = $this->postJson('/api/v1/runs', $payload, authHeader($this->token));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['heart_rate_samples.0.bpm']);
});

test('invalid nested pace split returns 422', function () {
    $payload = validRunPayload();
    $payload['pace_splits'] = [
        ['kilometer_number' => 1],
    ];

    $response = $this->postJson('/api/v1/runs', $payload, authHeader($this->token));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['pace_splits.0.split_time_seconds', 'pace_splits.0.pace_seconds_per_km', 'pace_splits.0.is_partial']);
});

test('unauthenticated request returns 401', function () {
    $response = $this->postJson('/api/v1/runs', validRunPayload());

    $response->assertUnauthorized();
});

test('user cannot see runs belonging to another user', function () {
    $otherUser = User::factory()->create();
    Run::factory()->for($otherUser)->create();

    $response = $this->getJson('/api/v1/runs', authHeader($this->token));

    $response->assertSuccessful();
    expect($response->json('data'))->toBeEmpty();
});

test('run can be created without optional fields', function () {
    $payload = validRunPayload();
    unset($payload['steps'], $payload['avg_heart_rate'], $payload['avg_pace_seconds_per_km'], $payload['heart_rate_samples'], $payload['pace_splits']);

    $response = $this->postJson('/api/v1/runs', $payload, authHeader($this->token));

    $response->assertCreated();
    $this->assertDatabaseCount('runs', 1);
    $this->assertDatabaseCount('heart_rate_samples', 0);
    $this->assertDatabaseCount('pace_splits', 0);
});

function validRunPayload(): array
{
    return [
        'start_time' => '2026-02-22T08:30:00Z',
        'end_time' => '2026-02-22T09:15:00Z',
        'distance_km' => 10.5,
        'duration_seconds' => 2700,
        'steps' => 12500,
        'avg_heart_rate' => 155,
        'avg_pace_seconds_per_km' => 257,
        'heart_rate_samples' => [
            ['timestamp' => '2026-02-22T08:30:00Z', 'bpm' => 120],
            ['timestamp' => '2026-02-22T08:30:05Z', 'bpm' => 125],
        ],
        'pace_splits' => [
            ['kilometer_number' => 1, 'split_time_seconds' => 260, 'pace_seconds_per_km' => 260, 'is_partial' => false, 'partial_distance_km' => null],
            ['kilometer_number' => 11, 'split_time_seconds' => 130, 'pace_seconds_per_km' => 260, 'is_partial' => true, 'partial_distance_km' => 0.5],
        ],
    ];
}

function authHeader(string $token): array
{
    return ['Authorization' => "Bearer {$token}"];
}
