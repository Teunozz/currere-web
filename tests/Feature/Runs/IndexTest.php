<?php

declare(strict_types=1);

use App\Models\Run;
use App\Models\User;

test('authenticated user sees runs page with paginated data', function () {
    $user = User::factory()->create();
    Run::factory()->for($user)->count(3)->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('runs/Index')
            ->has('runs.data', 3)
        );
});

test('runs are filtered by date range', function () {
    $user = User::factory()->create();

    Run::factory()->for($user)->create(['start_time' => '2026-01-15T08:00:00Z']);
    Run::factory()->for($user)->create(['start_time' => '2026-02-10T08:00:00Z']);
    Run::factory()->for($user)->create(['start_time' => '2026-02-20T08:00:00Z']);

    $this->actingAs($user)
        ->get('/dashboard?date_from=2026-02-01&date_to=2026-02-28')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('runs/Index')
            ->has('runs.data', 2)
        );
});

test('runs can be sorted by column', function () {
    $user = User::factory()->create();

    Run::factory()->for($user)->create(['distance_km' => 5.0]);
    Run::factory()->for($user)->create(['distance_km' => 15.0]);
    Run::factory()->for($user)->create(['distance_km' => 10.0]);

    $response = $this->actingAs($user)
        ->get('/dashboard?sort=distance_km&direction=desc');

    $response->assertSuccessful();

    $runs = $response->original->getData()['page']['props']['runs']['data'];
    $distances = array_column($runs, 'distance_km');
    expect($distances)->toEqual([15.0, 10.0, 5.0]);
});

test('unauthenticated users are redirected to login', function () {
    $this->get('/dashboard')
        ->assertRedirect('/login');
});

test('user only sees their own runs', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Run::factory()->for($user)->count(2)->create();
    Run::factory()->for($otherUser)->count(3)->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('runs.data', 2)
        );
});
