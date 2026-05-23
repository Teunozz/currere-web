<?php

declare(strict_types=1);

use App\Ai\Tools\ProposeRunEditTool;
use App\Models\Run;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    Cache::flush();
    $this->user = User::factory()->create();
});

test('exposes a snake_case tool name so the SSE formatter can match it', function () {
    expect((new ProposeRunEditTool(1))->name())->toBe('propose_run_edit');
});

test('returns a pending_action envelope and stores only the supplied changes', function () {
    $run = Run::factory()->for($this->user)->create([
        'distance_km' => 8.000,
        'avg_heart_rate' => 150,
    ]);

    $tool = new ProposeRunEditTool($this->user->id);
    $payload = json_decode((string) $tool->handle(new Request([
        'run_id' => $run->id,
        'distance_km' => 8.5,
    ])), true);

    expect($payload['pending_action']['type'])->toBe('edit_run');
    expect($payload['pending_action']['changes'])->toBe(['distance_km' => 8.5]);
    expect($payload['pending_action']['summary'])->toContain('distance_km');

    $cached = Cache::get('pending_action:'.$payload['pending_action']['id']);
    expect($cached['changes'])->toBe(['distance_km' => 8.5]);
    expect($cached['user_id'])->toBe($this->user->id);
});

test('does not mutate the run', function () {
    $run = Run::factory()->for($this->user)->create(['distance_km' => 8.000]);

    (new ProposeRunEditTool($this->user->id))
        ->handle(new Request(['run_id' => $run->id, 'distance_km' => 12]));

    expect((float) $run->fresh()->distance_km)->toBe(8.0);
});

test('errors when no editable fields are provided', function () {
    $run = Run::factory()->for($this->user)->create();

    $payload = json_decode((string) (new ProposeRunEditTool($this->user->id))
        ->handle(new Request(['run_id' => $run->id])), true);

    expect($payload)->toHaveKey('error');
    expect($payload)->not->toHaveKey('pending_action');
});

test('rejects a run owned by another user', function () {
    $other = User::factory()->create();
    $run = Run::factory()->for($other)->create();

    $payload = json_decode((string) (new ProposeRunEditTool($this->user->id))
        ->handle(new Request(['run_id' => $run->id, 'distance_km' => 5])), true);

    expect($payload)->toHaveKey('error');
});

test('ignores unknown fields and null values', function () {
    $run = Run::factory()->for($this->user)->create();

    $payload = json_decode((string) (new ProposeRunEditTool($this->user->id))
        ->handle(new Request([
            'run_id' => $run->id,
            'distance_km' => 10,
            'steps' => null,
            'nonsense' => 'ignored',
        ])), true);

    expect($payload['pending_action']['changes'])->toBe(['distance_km' => 10]);
});
