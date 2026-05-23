<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Queries\ComparePeriodsQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ComparePeriodsTool implements Tool
{
    public function __construct(private int $userId) {}

    public function description(): Stringable|string
    {
        return 'Compare aggregated run statistics for two date ranges side-by-side. Both periods are aggregated independently and returned under `period_a` and `period_b`. Dates should be ISO date strings (YYYY-MM-DD).';
    }

    public function handle(Request $request): Stringable|string
    {
        $fromA = (string) $request['from_a'];
        $toA = (string) $request['to_a'];
        $fromB = (string) $request['from_b'];
        $toB = (string) $request['to_b'];

        return json_encode(
            (new ComparePeriodsQuery($this->userId))->compare($fromA, $toA, $fromB, $toB),
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from_a' => $schema->string()->required(),
            'to_a' => $schema->string()->required(),
            'from_b' => $schema->string()->required(),
            'to_b' => $schema->string()->required(),
        ];
    }
}
