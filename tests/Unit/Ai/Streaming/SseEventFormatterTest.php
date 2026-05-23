<?php

declare(strict_types=1);

use App\Ai\Streaming\SseEventFormatter;
use Laravel\Ai\Responses\Data;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolCall;
use Laravel\Ai\Streaming\Events\ToolResult;

function makeToolResult(string $name, mixed $result): ToolResult
{
    return new ToolResult(
        id: 'evt-1',
        toolResult: new Data\ToolResult(
            id: 'tool-1',
            name: $name,
            arguments: [],
            result: $result,
        ),
        successful: true,
        error: null,
        timestamp: 0,
    );
}

test('text deltas pass through as a single SSE frame', function () {
    $event = new TextDelta(id: 'evt', messageId: 'm1', delta: 'hello', timestamp: 0);

    $frames = (new SseEventFormatter)->format($event);

    expect($frames)->toHaveCount(1);
    expect($frames[0])->toStartWith('data: ');
    expect($frames[0])->toEndWith("\n\n");
    expect($frames[0])->toContain('"type":"text_delta"');
    expect($frames[0])->toContain('"delta":"hello"');
});

test('tool calls pass through as a single SSE frame', function () {
    $event = new ToolCall(
        id: 'evt',
        toolCall: new Data\ToolCall(id: 'tool-1', name: 'fetch_runs', arguments: ['days' => 7]),
        timestamp: 0,
    );

    $frames = (new SseEventFormatter)->format($event);

    expect($frames)->toHaveCount(1);
    expect($frames[0])->toContain('"type":"tool_call"');
    expect($frames[0])->toContain('"tool_name":"fetch_runs"');
});

test('tool results from read-only tools pass through as a single frame', function () {
    $event = makeToolResult('fetch_runs', json_encode([['id' => 1]]));

    $frames = (new SseEventFormatter)->format($event);

    expect($frames)->toHaveCount(1);
    expect($frames[0])->toContain('"type":"tool_result"');
});

test('propose_run_delete results emit an additional pending_action frame', function () {
    $envelope = [
        'pending_action' => [
            'id' => 'abc-uuid',
            'type' => 'delete_run',
            'run_id' => 42,
            'changes' => [],
            'summary' => 'Delete run on 2026-05-20 — 8.20 km, 40 min',
            'expires_at' => '2026-05-23T12:00:00+00:00',
        ],
    ];

    $event = makeToolResult('propose_run_delete', json_encode($envelope));

    $frames = (new SseEventFormatter)->format($event);

    expect($frames)->toHaveCount(2);
    expect($frames[0])->toContain('"type":"tool_result"');

    $payload = json_decode(trim(substr($frames[1], strlen('data: '))), true);
    expect($payload['type'])->toBe('pending_action');
    expect($payload['id'])->toBe('abc-uuid');
    expect($payload['action_type'])->toBe('delete_run');
    expect($payload['run_id'])->toBe(42);
    expect($payload['summary'])->toContain('8.20 km');
});

test('propose_run_edit results emit a pending_action frame with the changes', function () {
    $envelope = [
        'pending_action' => [
            'id' => 'edit-uuid',
            'type' => 'edit_run',
            'run_id' => 7,
            'changes' => ['distance_km' => 8.5],
            'summary' => 'Edit run',
            'expires_at' => '2026-05-23T12:00:00+00:00',
        ],
    ];

    $frames = (new SseEventFormatter)->format(makeToolResult('propose_run_edit', json_encode($envelope)));

    $payload = json_decode(trim(substr($frames[1], strlen('data: '))), true);
    expect($payload['action_type'])->toBe('edit_run');
    expect($payload['changes'])->toBe(['distance_km' => 8.5]);
});

test('a propose tool result with malformed JSON does not crash', function () {
    $frames = (new SseEventFormatter)->format(makeToolResult('propose_run_delete', '{not json'));

    expect($frames)->toHaveCount(1);
});

test('a propose tool result missing the pending_action key emits only the original frame', function () {
    $frames = (new SseEventFormatter)->format(makeToolResult('propose_run_delete', json_encode(['error' => 'no run'])));

    expect($frames)->toHaveCount(1);
});

test('done returns the SSE done sentinel', function () {
    expect((new SseEventFormatter)->done())->toBe("data: [DONE]\n\n");
});
