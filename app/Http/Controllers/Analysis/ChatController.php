<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analysis;

use App\Ai\Agents\RunCoachAgent;
use App\Ai\Streaming\SseEventFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController
{
    public function stream(Request $request, SseEventFormatter $formatter): Response|JsonResponse
    {
        $validated = $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|string|in:user,assistant',
            'messages.*.content' => 'required|string',
            'timezone' => ['nullable', 'string', Rule::in(timezone_identifiers_list())],
        ]);

        $messages = $validated['messages'];
        $latest = array_pop($messages);

        if ($latest['role'] !== 'user') {
            return response()->json([
                'message' => 'The most recent message must be from the user.',
                'errors' => ['messages' => ['The most recent message must be from the user.']],
            ], 422);
        }

        $history = array_map(
            static fn (array $message) => $message['role'] === 'user'
                ? new UserMessage($message['content'])
                : new AssistantMessage($message['content']),
            $messages,
        );

        $timezone = $validated['timezone'] ?? config('app.timezone');

        $agent = new RunCoachAgent($request->user()->id, $history, $timezone);
        $stream = $agent->stream($latest['content']);

        return new StreamedResponse(function () use ($stream, $formatter): void {
            foreach ($stream as $event) {
                if (connection_aborted()) {
                    return;
                }

                foreach ($formatter->format($event) as $frame) {
                    echo $frame;
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            }

            echo $formatter->done();
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
