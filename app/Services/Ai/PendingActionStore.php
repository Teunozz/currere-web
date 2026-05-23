<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PendingActionStore
{
    public const TTL_SECONDS = 600;

    public const KEY_PREFIX = 'pending_action:';

    /**
     * Persist a pending chat-proposed action and return the stored payload,
     * stamped with `id` and `expires_at`.
     *
     * @param  array{type: string, run_id: int, user_id: int, changes: array<string, mixed>, summary: string}  $payload
     * @return array<string, mixed>
     */
    public function put(array $payload): array
    {
        $id = (string) Str::uuid();
        $expiresAt = now()->addSeconds(self::TTL_SECONDS);

        $stored = [
            ...$payload,
            'id' => $id,
            'expires_at' => $expiresAt->toIso8601String(),
        ];

        Cache::put(self::KEY_PREFIX.$id, $stored, self::TTL_SECONDS);

        return $stored;
    }

    /** @return array<string, mixed>|null */
    public function get(string $id): ?array
    {
        $value = Cache::get(self::KEY_PREFIX.$id);

        return is_array($value) ? $value : null;
    }

    public function forget(string $id): void
    {
        Cache::forget(self::KEY_PREFIX.$id);
    }
}
