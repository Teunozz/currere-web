<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Queries\RunStatsQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FetchRunStatsTool implements Tool
{
    public function __construct(
        private int $userId,
        private string $timezone = 'UTC',
    ) {}

    public function name(): string
    {
        return 'fetch_run_stats';
    }

    public function description(): Stringable|string
    {
        return 'Fetch aggregated run statistics for the user over a given period. Returns total distance, total time, run count, average pace, and best pace.';
    }

    public function handle(Request $request): Stringable|string
    {
        $days = $request['days'] ?? 90;

        return json_encode((new RunStatsQuery($this->userId, $this->timezone))->forPeriod($days));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()->min(1)->max(365),
        ];
    }
}
