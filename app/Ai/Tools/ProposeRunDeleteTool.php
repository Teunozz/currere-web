<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Run;
use App\Services\Ai\PendingActionStore;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ProposeRunDeleteTool implements Tool
{
    public function __construct(
        private int $userId,
        private PendingActionStore $store = new PendingActionStore,
    ) {}

    public function name(): string
    {
        return 'propose_run_delete';
    }

    public function description(): Stringable|string
    {
        return 'Propose deleting one of the user\'s runs. This NEVER deletes directly — it returns a pending_action envelope and the UI renders a confirm card. Your text reply should be a single short sentence describing the proposal (no JSON, no UUID).';
    }

    public function handle(Request $request): Stringable|string
    {
        $runId = (int) ($request['run_id'] ?? 0);

        $run = Run::query()
            ->where('user_id', $this->userId)
            ->find($runId);

        if (! $run instanceof Run) {
            return json_encode([
                'error' => "Run {$runId} was not found or does not belong to the user.",
            ]);
        }

        $summary = sprintf(
            'Delete run on %s — %s km, %d min',
            $run->start_time->toDateString(),
            number_format((float) $run->distance_km, 2),
            (int) round($run->duration_seconds / 60),
        );

        $stored = $this->store->put([
            'type' => 'delete_run',
            'run_id' => $run->id,
            'user_id' => $this->userId,
            'changes' => [],
            'summary' => $summary,
        ]);

        return json_encode(['pending_action' => $stored]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'run_id' => $schema->integer()->required(),
        ];
    }
}
