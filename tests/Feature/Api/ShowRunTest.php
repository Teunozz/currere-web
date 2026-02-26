<?php

declare(strict_types=1);

use App\Models\HeartRateSample;
use App\Models\PaceSplit;
use App\Models\Run;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-device')->plainTextToken;
});

test('authenticated user can view run detail with heart rate samples and pace splits', function () {
    $run = Run::factory()->for($this->user)->create();
    HeartRateSample::factory()->for($run)->count(3)->create();
    PaceSplit::factory()->for($run)->count(5)->create();

    $response = $this->getJson("/api/v1/runs/{$run->id}", [
        'Authorization' => "Bearer {$this->token}",
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $run->id)
        ->assertJsonCount(3, 'data.heart_rate_samples')
        ->assertJsonCount(5, 'data.pace_splits');
});

test('run detail includes all expected fields', function () {
    $run = Run::factory()->for($this->user)->create([
        'distance_km' => 10.5,
        'duration_seconds' => 2700,
        'steps' => 12500,
        'avg_heart_rate' => 155,
        'avg_pace_seconds_per_km' => 257,
    ]);

    $response = $this->getJson("/api/v1/runs/{$run->id}", [
        'Authorization' => "Bearer {$this->token}",
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'id',
                'start_time',
                'end_time',
                'distance_km',
                'duration_seconds',
                'steps',
                'avg_heart_rate',
                'avg_pace_seconds_per_km',
                'created_at',
                'heart_rate_samples',
                'pace_splits',
            ],
        ]);
});

test('user cannot view another users run', function () {
    $otherUser = User::factory()->create();
    $run = Run::factory()->for($otherUser)->create();

    $response = $this->getJson("/api/v1/runs/{$run->id}", [
        'Authorization' => "Bearer {$this->token}",
    ]);

    $response->assertNotFound();
});

test('non-existent run returns 404', function () {
    $response = $this->getJson('/api/v1/runs/99999', [
        'Authorization' => "Bearer {$this->token}",
    ]);

    $response->assertNotFound();
});

test('unauthenticated request returns 401', function () {
    $run = Run::factory()->create();

    $response = $this->getJson("/api/v1/runs/{$run->id}");

    $response->assertUnauthorized();
});
