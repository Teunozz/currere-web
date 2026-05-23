<?php

declare(strict_types=1);

use App\Models\Run;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('unauthenticated user is redirected', function () {
    $run = Run::factory()->create();

    $this->patchJson("/runs/{$run->id}", ['distance_km' => 5])
        ->assertUnauthorized();
});

test('user cannot update another users run', function () {
    $other = User::factory()->create();
    $run = Run::factory()->for($other)->create();

    $this->actingAs($this->user)
        ->patchJson("/runs/{$run->id}", ['distance_km' => 5])
        ->assertForbidden();
});

test('partial update only touches the supplied field', function () {
    $run = Run::factory()->for($this->user)->create([
        'distance_km' => 5.000,
        'duration_seconds' => 1800,
        'avg_heart_rate' => 150,
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson("/runs/{$run->id}", ['distance_km' => 8.5]);

    $response->assertOk();
    $run->refresh();

    expect((float) $run->distance_km)->toBe(8.5);
    expect($run->duration_seconds)->toBe(1800);
    expect($run->avg_heart_rate)->toBe(150);
});

test('full update applies every editable field', function () {
    $run = Run::factory()->for($this->user)->create();

    $changes = [
        'start_time' => '2026-05-01T09:00:00+00:00',
        'end_time' => '2026-05-01T09:45:00+00:00',
        'distance_km' => 9.5,
        'duration_seconds' => 2700,
        'steps' => 9100,
        'avg_heart_rate' => 158,
        'avg_pace_seconds_per_km' => 284,
    ];

    $response = $this->actingAs($this->user)->patchJson("/runs/{$run->id}", $changes);

    $response->assertOk();
    $run->refresh();

    expect((float) $run->distance_km)->toBe(9.5);
    expect($run->duration_seconds)->toBe(2700);
    expect($run->steps)->toBe(9100);
    expect($run->avg_heart_rate)->toBe(158);
    expect($run->avg_pace_seconds_per_km)->toBe(284);
});

test('rejects an end_time before start_time', function () {
    $run = Run::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->patchJson("/runs/{$run->id}", [
            'start_time' => '2026-05-01T09:00:00+00:00',
            'end_time' => '2026-05-01T08:00:00+00:00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['end_time']);
});

test('rejects negative or zero distances', function () {
    $run = Run::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->patchJson("/runs/{$run->id}", ['distance_km' => 0])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['distance_km']);
});

test('returns the updated run wrapped in a run key', function () {
    $run = Run::factory()->for($this->user)->create();

    $response = $this->actingAs($this->user)
        ->patchJson("/runs/{$run->id}", ['distance_km' => 6.25]);

    $response->assertOk();
    $response->assertJsonPath('run.id', $run->id);
    $response->assertJsonPath('run.distance_km', 6.25);
});
