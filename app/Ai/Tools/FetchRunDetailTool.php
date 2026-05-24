<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Queries\RunDetailQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FetchRunDetailTool implements Tool
{
    public function __construct(
        private int $userId,
        private string $timezone = 'UTC',
    ) {}

    public function name(): string
    {
        return 'fetch_run_detail';
    }

    public function description(): Stringable|string
    {
        return 'Fetch a single run with its heart rate samples and pace splits. Returns the run data plus a `url` for citation, or `{ "error": ... }` if the run does not exist or belongs to another user.';
    }

    public function handle(Request $request): Stringable|string
    {
        $runId = (int) ($request['run_id'] ?? 0);

        $detail = (new RunDetailQuery($this->userId, $this->timezone))->forRun($runId);

        if ($detail === null) {
            return json_encode(['error' => 'Run not found']);
        }

        return json_encode($detail + ['url' => route('runs.show', $detail['id'])]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'run_id' => $schema->integer()->required(),
        ];
    }
}
