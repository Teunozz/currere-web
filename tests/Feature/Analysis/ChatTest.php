<?php

declare(strict_types=1);

use App\Ai\Agents\RunCoachAgent;
use App\Ai\Tools\ComparePeriodsTool;
use App\Ai\Tools\FetchHeartRateDataTool;
use App\Ai\Tools\FetchPersonalBestsTool;
use App\Ai\Tools\FetchRunDetailTool;
use App\Ai\Tools\FetchRunStatsTool;
use App\Ai\Tools\FetchRunsTool;
use App\Models\Run;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

function streamedText(string $body): string
{
    $text = '';

    foreach (explode("\n\n", $body) as $line) {
        if (! str_starts_with($line, 'data: ') || $line === 'data: [DONE]') {
            continue;
        }

        $payload = json_decode(substr($line, 6), true);

        if (is_array($payload) && ($payload['type'] ?? null) === 'text_delta') {
            $text .= $payload['delta'] ?? '';
        }
    }

    return $text;
}

test('unauthenticated user cannot reach the chat stream', function () {
    $this->postJson('/analysis/chat', [
        'messages' => [['role' => 'user', 'content' => 'Hi']],
    ])->assertUnauthorized();
});

test('stream returns SSE envelope with data lines and trailing DONE marker', function () {
    Run::factory()->for($this->user)->count(3)->create();

    RunCoachAgent::fake(['Your last three runs look strong.']);

    $response = $this->actingAs($this->user)
        ->postJson('/analysis/chat', [
            'messages' => [
                ['role' => 'user', 'content' => 'How am I doing?'],
            ],
        ]);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/event-stream');

    $body = $response->streamedContent();

    expect($body)->toContain('data: ');
    expect($body)->toEndWith("data: [DONE]\n\n");
});

test('streamed body relays citation links produced by the model', function () {
    $run = Run::factory()->for($this->user)->create();

    $citation = '[2026-05-20 — 8.2 km]('.route('runs.show', $run->id).')';

    RunCoachAgent::fake(["Great work on {$citation}!"]);

    $body = $this->actingAs($this->user)
        ->postJson('/analysis/chat', [
            'messages' => [
                ['role' => 'user', 'content' => 'Tell me about my last run.'],
            ],
        ])
        ->streamedContent();

    expect(streamedText($body))->toContain($citation);
});

test('user with zero runs still gets a streamed response', function () {
    RunCoachAgent::fake(['Add a run to unlock deeper analysis.']);

    $response = $this->actingAs($this->user)
        ->postJson('/analysis/chat', [
            'messages' => [
                ['role' => 'user', 'content' => 'What do you see?'],
            ],
        ]);

    $response->assertOk();
    expect(streamedText($response->streamedContent()))->toBe('Add a run to unlock deeper analysis.');
});

test('prior assistant turns are passed as conversation history', function () {
    Run::factory()->for($this->user)->count(3)->create();

    RunCoachAgent::fake(['Sure, here is a follow-up.']);

    $response = $this->actingAs($this->user)
        ->postJson('/analysis/chat', [
            'messages' => [
                ['role' => 'user', 'content' => 'How am I doing?'],
                ['role' => 'assistant', 'content' => 'You ran 30 km last week.'],
                ['role' => 'user', 'content' => 'And the week before?'],
            ],
        ]);

    $response->assertOk();
    expect(streamedText($response->streamedContent()))->toContain('follow-up');
});

test('empty messages array fails validation', function () {
    $this->actingAs($this->user)
        ->postJson('/analysis/chat', ['messages' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['messages']);
});

test('last message must be from the user', function () {
    $this->actingAs($this->user)
        ->postJson('/analysis/chat', [
            'messages' => [
                ['role' => 'user', 'content' => 'Hi'],
                ['role' => 'assistant', 'content' => 'Hello'],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['messages']);
});

test('agent wires up all six read tools so tool calls can stream as separate events', function () {
    $tools = collect((new RunCoachAgent(1))->tools())->map(fn ($tool) => $tool::class)->all();

    expect($tools)->toEqualCanonicalizing([
        FetchRunsTool::class,
        FetchRunStatsTool::class,
        FetchHeartRateDataTool::class,
        FetchRunDetailTool::class,
        ComparePeriodsTool::class,
        FetchPersonalBestsTool::class,
    ]);
});

test('system prompt instructs the model to cite runs by url', function () {
    $instructions = (string) (new RunCoachAgent(1))->instructions();

    expect($instructions)->toContain('url')
        ->and($instructions)->toContain('Citation');
});
