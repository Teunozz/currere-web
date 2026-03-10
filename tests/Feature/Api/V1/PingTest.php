<?php

declare(strict_types=1);

use App\Models\User;

test('authenticated user receives ok status', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;

    $response = $this->getJson('/api/v1/ping', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertSuccessful()
        ->assertJson(['status' => 'ok']);
});

test('unauthenticated request returns 401', function () {
    $response = $this->getJson('/api/v1/ping');

    $response->assertUnauthorized();
});
