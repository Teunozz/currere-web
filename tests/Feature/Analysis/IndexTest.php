<?php

declare(strict_types=1);

use App\Models\Run;
use App\Models\User;

test('unauthenticated user is redirected from the analysis page', function () {
    $this->get('/analysis')->assertRedirect('/login');
});

test('analysis page renders the chat shell with starter prompts and run count', function () {
    $user = User::factory()->create();
    Run::factory()->for($user)->count(4)->create();

    $this->actingAs($user)
        ->get('/analysis')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('analysis/Index')
            ->where('runCount', 4)
            ->has('starterPrompts', 6)
            ->has('starterPrompts.0', fn ($prompt) => $prompt
                ->has('id')
                ->has('label')
                ->has('prompt')
            )
        );
});

test('starter prompts come from the analysis config', function () {
    $user = User::factory()->create();

    $configured = config('analysis.starter_prompts');

    $this->actingAs($user)
        ->get('/analysis')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('starterPrompts', $configured)
        );
});

test('run count reflects only the authenticated user runs', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Run::factory()->for($user)->count(2)->create();
    Run::factory()->for($other)->count(5)->create();

    $this->actingAs($user)
        ->get('/analysis')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('runCount', 2));
});
