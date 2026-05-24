<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Run;
use App\Services\Ai\PendingActionStore;
use App\Services\Runs\UpdateRunAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ProposeRunEditTool implements Tool
{
    public function __construct(
        private int $userId,
        private string $timezone = 'UTC',
        private PendingActionStore $store = new PendingActionStore,
    ) {}

    public function name(): string
    {
        return 'propose_run_edit';
    }

    public function description(): Stringable|string
    {
        return 'Propose editing one of the user\'s runs. This NEVER mutates directly — it returns a pending_action envelope and the UI renders a confirm card. Supply only the fields you want to change. Your text reply should be a single short sentence describing the proposal (no JSON, no UUID).';
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

        $arguments = $request->toArray();
        $changes = [];
        foreach (UpdateRunAction::EDITABLE_FIELDS as $field) {
            if (array_key_exists($field, $arguments) && $arguments[$field] !== null) {
                $changes[$field] = $arguments[$field];
            }
        }

        if ($changes === []) {
            return json_encode([
                'error' => 'No editable fields were provided. Supply at least one of: '.implode(', ', UpdateRunAction::EDITABLE_FIELDS).'.',
            ]);
        }

        $summary = $this->buildSummary($run, $changes);

        $stored = $this->store->put([
            'type' => 'edit_run',
            'run_id' => $run->id,
            'user_id' => $this->userId,
            'changes' => $changes,
            'summary' => $summary,
        ]);

        return json_encode(['pending_action' => $stored]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'run_id' => $schema->integer()->required(),
            'start_time' => $schema->string(),
            'end_time' => $schema->string(),
            'distance_km' => $schema->number(),
            'duration_seconds' => $schema->integer(),
            'steps' => $schema->integer(),
            'avg_heart_rate' => $schema->integer(),
            'avg_pace_seconds_per_km' => $schema->integer(),
        ];
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function buildSummary(Run $run, array $changes): string
    {
        $parts = [];
        foreach ($changes as $field => $newValue) {
            $current = $run->getAttribute($field);
            $parts[] = sprintf('%s %s → %s', $field, $this->formatValue($current), $this->formatValue($newValue));
        }

        return sprintf(
            'Edit run on %s: %s',
            $run->start_time->copy()->setTimezone($this->timezone)->toDateString(),
            implode(', ', $parts),
        );
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_object($value) && method_exists($value, 'copy') && method_exists($value, 'setTimezone')) {
            return $value->copy()->setTimezone($this->timezone)->toDateTimeString();
        }

        return (string) $value;
    }
}
