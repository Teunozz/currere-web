<?php

declare(strict_types=1);

use App\Models\HeartRateSample;
use App\Models\PaceSplit;
use App\Models\Run;
use App\Models\User;

test('user can delete their own run', function () {
    $user = User::factory()->create();
    $run = Run::factory()->for($user)->create();
    HeartRateSample::factory()->for($run)->count(3)->create();
    PaceSplit::factory()->for($run)->count(2)->create();

    $this->actingAs($user)
        ->delete("/runs/{$run->id}")
        ->assertRedirect('/dashboard');

    $this->assertDatabaseMissing('runs', ['id' => $run->id]);
    $this->assertDatabaseCount('heart_rate_samples', 0);
    $this->assertDatabaseCount('pace_splits', 0);
});

test('user cannot delete another users run', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $run = Run::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->delete("/runs/{$run->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('runs', ['id' => $run->id]);
});

test('unauthenticated user is redirected', function () {
    $run = Run::factory()->create();

    $this->delete("/runs/{$run->id}")
        ->assertRedirect('/login');
});
