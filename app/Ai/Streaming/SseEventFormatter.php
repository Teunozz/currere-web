<?php

declare(strict_types=1);

namespace App\Ai\Streaming;

use Laravel\Ai\Streaming\Events\StreamEvent;
use Laravel\Ai\Streaming\Events\ToolResult;

class SseEventFormatter
{
    /** @var list<string> */
    private const PROPOSE_TOOLS = ['propose_run_edit', 'propose_run_delete'];

    /**
     * Convert a streamed agent event into one or two SSE `data: ...\n\n` frames.
     *
     * Standard events pass through unchanged (matching the SDK protocol the
     * existing client expects). When the event is a tool result from a
     * `propose_*` tool, an additional `pending_action` frame is emitted so the
     * Svelte UI can render a confirm card without scraping the LLM's prose.
     *
     * @return list<string>
     */
    public function format(StreamEvent $event): array
    {
        $frames = ['data: '.((string) $event)."\n\n"];

        if ($event instanceof ToolResult && in_array($event->toolResult->name, self::PROPOSE_TOOLS, true)) {
            $pending = $this->extractPendingAction($event->toolResult->result);

            if ($pending !== null) {
                $frames[] = 'data: '.json_encode([
                    'type' => 'pending_action',
                    'id' => $pending['id'],
                    'action_type' => $pending['type'],
                    'run_id' => $pending['run_id'],
                    'changes' => $pending['changes'],
                    'summary' => $pending['summary'],
                    'expires_at' => $pending['expires_at'],
                ])."\n\n";
            }
        }

        return $frames;
    }

    public function done(): string
    {
        return "data: [DONE]\n\n";
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractPendingAction(mixed $result): ?array
    {
        if (is_string($result)) {
            $decoded = json_decode($result, true);
        } elseif (is_array($result)) {
            $decoded = $result;
        } else {
            return null;
        }

        if (! is_array($decoded) || ! isset($decoded['pending_action']) || ! is_array($decoded['pending_action'])) {
            return null;
        }

        $pa = $decoded['pending_action'];

        foreach (['id', 'type', 'run_id', 'changes', 'summary', 'expires_at'] as $key) {
            if (! array_key_exists($key, $pa)) {
                return null;
            }
        }

        return $pa;
    }
}
