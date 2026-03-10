<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('authenticated user can view tokens page', function () {
    $this->actingAs($this->user)
        ->get('/settings/tokens')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Tokens')
            ->has('tokens')
        );
});

test('user can create a token and receives plain text value', function () {
    $response = $this->actingAs($this->user)
        ->post('/settings/tokens', ['name' => 'Samsung Galaxy S25']);

    $response->assertRedirect('/settings/tokens');

    $this->assertDatabaseHas('personal_access_tokens', [
        'name' => 'Samsung Galaxy S25',
    ]);
});

test('token list shows names but not plain text values', function () {
    $this->user->createToken('Device One');
    $this->user->createToken('Device Two');

    $this->actingAs($this->user)
        ->get('/settings/tokens')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('tokens', 2)
            ->where('tokens.0.name', 'Device One')
            ->missing('tokens.0.token')
        );
});

test('user can revoke a token', function () {
    $token = $this->user->createToken('Test Device');

    $this->actingAs($this->user)
        ->delete("/settings/tokens/{$token->accessToken->id}")
        ->assertRedirect('/settings/tokens');

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->id,
    ]);
});

test('revoked token returns 401 on API call', function () {
    $token = $this->user->createToken('Test Device');
    $plainToken = $token->plainTextToken;

    $this->user->tokens()->where('id', $token->accessToken->id)->delete();

    $this->getJson('/api/v1/ping', [
        'Authorization' => "Bearer {$plainToken}",
    ])->assertUnauthorized();
});

test('token name is required', function () {
    $this->actingAs($this->user)
        ->post('/settings/tokens', ['name' => ''])
        ->assertSessionHasErrors('name');
});

test('unauthenticated user is redirected', function () {
    $this->get('/settings/tokens')
        ->assertRedirect('/login');
});
