<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Requests\Settings\StoreTokenRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TokenController
{
    public function index(Request $request): Response
    {
        $tokens = $request->user()->tokens()->orderByDesc('created_at')->get()->map(fn ($token) => [
            'id' => $token->id,
            'name' => $token->name,
            'created_at' => $token->created_at->toIso8601String(),
            'last_used_at' => $token->last_used_at?->toIso8601String(),
        ]);

        return Inertia::render('settings/Tokens', [
            'tokens' => $tokens,
            'newToken' => session('newToken'),
            'baseUrl' => url('/api/v1'),
        ]);
    }

    public function store(StoreTokenRequest $request): RedirectResponse
    {
        $token = $request->user()->createToken($request->validated('name'));

        return redirect()->route('tokens.index')->with('newToken', $token->plainTextToken);
    }

    public function destroy(Request $request, int $tokenId): RedirectResponse
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();

        return redirect()->route('tokens.index');
    }
}
