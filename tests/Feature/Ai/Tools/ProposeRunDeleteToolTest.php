<?php

declare(strict_types=1);

use App\Ai\Tools\ProposeRunDeleteTool;
use App\Models\Run;
use App\Models\User;
use App\Services\Ai\PendingActionStore;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    Cache::flush();
    $this->user = User::factory()->create();
});

test('exposes a snake_case tool name so the SSE formatter can match it', function () {
    expect((new ProposeRunDeleteTool(1))->name())->toBe('propose_run_delete');
});

test('returns a pending_action envelope and stores it in cache', function () {
    $run = Run::factory()->for($this->user)->create([
        'distance_km' => 8.20,
        'duration_seconds' => 2400,
    ]);

    $tool = new ProposeRunDeleteTool($this->user->id);
    $payload = json_decode((string) $tool->handle(new Request(['run_id' => $run->id])), true);

    expect($payload['pending_action']['type'])->toBe('delete_run');
    expect($payload['pending_action']['run_id'])->toBe($run->id);
    expect($payload['pending_action']['summary'])->toContain('8.20 km');
    expect($payload['pending_action']['summary'])->toContain('40 min');
    expect($payload['pending_action']['id'])->toBeString();

    $cached = Cache::get('pending_action:'.$payload['pending_action']['id']);
    expect($cached['user_id'])->toBe($this->user->id);
    expect($cached['type'])->toBe('delete_run');
    expect($cached['run_id'])->toBe($run->id);
});

test('does not delete the run when called', function () {
    $run = Run::factory()->for($this->user)->create();

    (new ProposeRunDeleteTool($this->user->id))
        ->handle(new Request(['run_id' => $run->id]));

    $this->assertDatabaseHas('runs', ['id' => $run->id]);
});

test('rejects a run owned by another user', function () {
    $other = User::factory()->create();
    $run = Run::factory()->for($other)->create();

    $payload = json_decode((string) (new ProposeRunDeleteTool($this->user->id))
        ->handle(new Request(['run_id' => $run->id])), true);

    expect($payload)->toHaveKey('error');
    expect($payload)->not->toHaveKey('pending_action');
});

test('returns an error envelope when the run does not exist', function () {
    $payload = json_decode((string) (new ProposeRunDeleteTool($this->user->id))
        ->handle(new Request(['run_id' => 999999])), true);

    expect($payload)->toHaveKey('error');
});

test('cached entry expires after the configured TTL', function () {
    $run = Run::factory()->for($this->user)->create();

    $payload = json_decode((string) (new ProposeRunDeleteTool($this->user->id))
        ->handle(new Request(['run_id' => $run->id])), true);

    $id = $payload['pending_action']['id'];
    expect(Cache::get('pending_action:'.$id))->not->toBeNull();

    $this->travel(PendingActionStore::TTL_SECONDS + 5)->seconds();

    expect(Cache::get('pending_action:'.$id))->toBeNull();
});
