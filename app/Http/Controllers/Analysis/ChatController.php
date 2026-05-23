<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analysis;

use App\Ai\Agents\RunCoachAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;
use Symfony\Component\HttpFoundation\Response;

class ChatController
{
    public function stream(Request $request): Response|JsonResponse
    {
        $validated = $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|string|in:user,assistant',
            'messages.*.content' => 'required|string',
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

        $agent = new RunCoachAgent($request->user()->id, $history);

        return $agent->stream($latest['content'])->toResponse($request);
    }
}
