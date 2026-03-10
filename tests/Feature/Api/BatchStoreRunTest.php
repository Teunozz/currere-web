<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-device')->plainTextToken;
});

test('batch sync creates multiple runs and returns counts', function () {
    $payload = [
        'runs' => [
            makeRunPayload('2026-02-20T08:00:00Z', '2026-02-20T08:30:00Z', 5.0, 1800),
            makeRunPayload('2026-02-21T08:00:00Z', '2026-02-21T09:00:00Z', 10.0, 3600),
            makeRunPayload('2026-02-22T08:00:00Z', '2026-02-22T08:20:00Z', 3.0, 1200),
        ],
    ];

    $response = $this->postJson('/api/v1/runs/batch', $payload, [
        'Authorization' => "Bearer {$this->token}",
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.created', 3)
        ->assertJsonPath('data.skipped', 0);

    expect($response->json('data.results'))->toHaveCount(3);
    $this->assertDatabaseCount('runs', 3);
});

test('batch sync skips duplicates and reports mixed results', function () {
    $existingPayload = makeRunPayload('2026-02-20T08:00:00Z', '2026-02-20T08:30:00Z', 5.0, 1800);
    $this->postJson('/api/v1/runs/batch', ['runs' => [$existingPayload]], [
        'Authorization' => "Bearer {$this->token}",
    ])->assertCreated();

    $payload = [
        'runs' => [
            makeRunPayload('2026-02-20T08:00:00Z', '2026-02-20T08:30:00Z', 5.0, 1800),
            makeRunPayload('2026-02-21T08:00:00Z', '2026-02-21T09:00:00Z', 10.0, 3600),
        ],
    ];

    $response = $this->postJson('/api/v1/runs/batch', $payload, [
        'Authorization' => "Bearer {$this->token}",
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.created', 1)
        ->assertJsonPath('data.skipped', 1);

    $results = $response->json('data.results');
    expect($results[0]['status'])->toBe('skipped')
        ->and($results[1]['status'])->toBe('created');

    $this->assertDatabaseCount('runs', 2);
});

test('batch sync returns 422 on invalid run in batch', function () {
    $payload = [
        'runs' => [
            makeRunPayload('2026-02-20T08:00:00Z', '2026-02-20T08:30:00Z', 5.0, 1800),
            ['distance_km' => 10.0],
        ],
    ];

    $response = $this->postJson('/api/v1/runs/batch', $payload, [
        'Authorization' => "Bearer {$this->token}",
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['runs.1.start_time']);
});

test('batch sync requires runs array', function () {
    $response = $this->postJson('/api/v1/runs/batch', [], [
        'Authorization' => "Bearer {$this->token}",
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['runs']);
});

test('unauthenticated batch request returns 401', function () {
    $response = $this->postJson('/api/v1/runs/batch', [
        'runs' => [makeRunPayload('2026-02-20T08:00:00Z', '2026-02-20T08:30:00Z', 5.0, 1800)],
    ]);

    $response->assertUnauthorized();
});

function makeRunPayload(string $startTime, string $endTime, float $distanceKm, int $durationSeconds): array
{
    return [
        'start_time' => $startTime,
        'end_time' => $endTime,
        'distance_km' => $distanceKm,
        'duration_seconds' => $durationSeconds,
        'steps' => 6000,
        'avg_heart_rate' => 150,
        'avg_pace_seconds_per_km' => (int) round($durationSeconds / $distanceKm),
    ];
}
