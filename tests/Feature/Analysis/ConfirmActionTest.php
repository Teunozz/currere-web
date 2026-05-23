<?php

declare(strict_types=1);

use App\Models\Run;
use App\Models\User;
use App\Services\Ai\PendingActionStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

beforeEach(function () {
    Cache::flush();
    $this->user = User::factory()->create();
    $this->store = new PendingActionStore;
});

test('returns 404 when the pending action does not exist', function () {
    $this->actingAs($this->user)
        ->postJson('/analysis/actions/'.Str::uuid().'/confirm')
        ->assertNotFound()
        ->assertJsonPath('message', 'This action has expired or is invalid.');
});

test('returns 403 when the action belongs to a different user', function () {
    $other = User::factory()->create();
    $run = Run::factory()->for($other)->create();

    $stored = $this->store->put([
        'type' => 'delete_run',
        'run_id' => $run->id,
        'user_id' => $other->id,
        'changes' => [],
        'summary' => 'Delete run',
    ]);

    $this->actingAs($this->user)
        ->postJson("/analysis/actions/{$stored['id']}/confirm")
        ->assertForbidden();
});

test('confirms a delete and clears the cache entry', function () {
    $run = Run::factory()->for($this->user)->create();

    $stored = $this->store->put([
        'type' => 'delete_run',
        'run_id' => $run->id,
        'user_id' => $this->user->id,
        'changes' => [],
        'summary' => 'Delete run',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/analysis/actions/{$stored['id']}/confirm");

    $response->assertOk()
        ->assertJsonPath('status', 'executed')
        ->assertJsonPath('result.deleted_run_id', $run->id);

    $this->assertDatabaseMissing('runs', ['id' => $run->id]);
    expect(Cache::get('pending_action:'.$stored['id']))->toBeNull();
});

test('a confirm token can only be used once', function () {
    $run = Run::factory()->for($this->user)->create();

    $stored = $this->store->put([
        'type' => 'delete_run',
        'run_id' => $run->id,
        'user_id' => $this->user->id,
        'changes' => [],
        'summary' => 'Delete run',
    ]);

    $this->actingAs($this->user)
        ->postJson("/analysis/actions/{$stored['id']}/confirm")
        ->assertOk();

    $this->actingAs($this->user)
        ->postJson("/analysis/actions/{$stored['id']}/confirm")
        ->assertNotFound();
});

test('confirms an edit and applies only the stored changes', function () {
    $run = Run::factory()->for($this->user)->create([
        'distance_km' => 5.000,
        'duration_seconds' => 1500,
        'avg_heart_rate' => 145,
    ]);

    $stored = $this->store->put([
        'type' => 'edit_run',
        'run_id' => $run->id,
        'user_id' => $this->user->id,
        'changes' => ['distance_km' => 6.5],
        'summary' => 'Edit run',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/analysis/actions/{$stored['id']}/confirm");

    $response->assertOk()
        ->assertJsonPath('status', 'executed')
        ->assertJsonPath('result.run.id', $run->id)
        ->assertJsonPath('result.run.distance_km', 6.5);

    $run->refresh();
    expect((float) $run->distance_km)->toBe(6.5);
    expect($run->duration_seconds)->toBe(1500);
    expect($run->avg_heart_rate)->toBe(145);

    expect(Cache::get('pending_action:'.$stored['id']))->toBeNull();
});

test('rejects an edit whose stored changes fail validation', function () {
    $run = Run::factory()->for($this->user)->create();

    $stored = $this->store->put([
        'type' => 'edit_run',
        'run_id' => $run->id,
        'user_id' => $this->user->id,
        'changes' => ['distance_km' => -1],
        'summary' => 'Bad edit',
    ]);

    $this->actingAs($this->user)
        ->postJson("/analysis/actions/{$stored['id']}/confirm")
        ->assertUnprocessable();
});

test('returns 404 and clears the cache if the run has been deleted out-of-band', function () {
    $run = Run::factory()->for($this->user)->create();

    $stored = $this->store->put([
        'type' => 'delete_run',
        'run_id' => $run->id,
        'user_id' => $this->user->id,
        'changes' => [],
        'summary' => 'Delete run',
    ]);

    $run->delete();

    $this->actingAs($this->user)
        ->postJson("/analysis/actions/{$stored['id']}/confirm")
        ->assertNotFound();

    expect(Cache::get('pending_action:'.$stored['id']))->toBeNull();
});

test('rejects an action with an unknown type', function () {
    Cache::put('pending_action:bogus', [
        'id' => 'bogus',
        'type' => 'who_knows',
        'run_id' => 0,
        'user_id' => $this->user->id,
        'changes' => [],
        'summary' => '',
        'expires_at' => now()->addMinutes(10)->toIso8601String(),
    ], 600);

    // Need a run that exists so we get past the run lookup branch.
    $run = Run::factory()->for($this->user)->create();
    Cache::put('pending_action:bogus2', [
        'id' => 'bogus2',
        'type' => 'who_knows',
        'run_id' => $run->id,
        'user_id' => $this->user->id,
        'changes' => [],
        'summary' => '',
        'expires_at' => now()->addMinutes(10)->toIso8601String(),
    ], 600);

    // Route requires a UUID so we can't hit `/bogus2/confirm` directly; assert
    // the underlying controller throws when type is unsupported by calling it
    // via a real UUID-stored action.
    $stored = $this->store->put([
        'type' => 'wat',
        'run_id' => $run->id,
        'user_id' => $this->user->id,
        'changes' => [],
        'summary' => '',
    ]);

    $this->actingAs($this->user)
        ->postJson("/analysis/actions/{$stored['id']}/confirm")
        ->assertUnprocessable();
});

test('requires authentication', function () {
    $this->postJson('/analysis/actions/'.Str::uuid().'/confirm')
        ->assertUnauthorized();
});
