<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Queries\HeartRateQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FetchHeartRateDataTool implements Tool
{
    public function __construct(private int $userId) {}

    public function description(): Stringable|string
    {
        return 'Fetch heart rate data across recent runs. Returns per-run average HR, max observed HR, and time-in-zone estimates.';
    }

    public function handle(Request $request): Stringable|string
    {
        $days = $request['days'] ?? 30;

        return json_encode((new HeartRateQuery($this->userId))->forPeriod($days));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()->min(1)->max(365),
        ];
    }
}
