<?php

declare(strict_types=1);

use App\Models\HeartRateSample;
use App\Models\PaceSplit;
use App\Models\Run;
use App\Models\User;

test('authenticated user can view run detail with heart rate samples and pace splits', function () {
    $user = User::factory()->create();
    $run = Run::factory()->for($user)->create();
    HeartRateSample::factory()->for($run)->count(3)->create();
    PaceSplit::factory()->for($run)->count(5)->create();

    $this->actingAs($user)
        ->get("/runs/{$run->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('runs/Show')
            ->has('run.data.heart_rate_samples', 3)
            ->has('run.data.pace_splits', 5)
        );
});

test('user cannot view another users run', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $run = Run::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->get("/runs/{$run->id}")
        ->assertForbidden();
});

test('unauthenticated user is redirected', function () {
    $run = Run::factory()->create();

    $this->get("/runs/{$run->id}")
        ->assertRedirect('/login');
});
