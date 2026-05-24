<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Queries\PersonalBestsQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FetchPersonalBestsTool implements Tool
{
    public function __construct(
        private int $userId,
        private string $timezone = 'UTC',
    ) {}

    public function name(): string
    {
        return 'fetch_personal_bests';
    }

    public function description(): Stringable|string
    {
        return 'Fetch the user\'s personal bests at the standard race distance milestones (1k, 5k, 10k, HM, M). Each entry includes the underlying run id, the run date, the run\'s actual distance, the average pace, and a predicted finish time at the milestone distance, plus a `url` for citation. Entries are null when no eligible run exists.';
    }

    public function handle(Request $request): Stringable|string
    {
        $bests = (new PersonalBestsQuery($this->userId, $this->timezone))->forUser();

        $rows = [];
        foreach ($bests as $milestone => $pb) {
            if ($pb === null) {
                $rows[$milestone] = null;

                continue;
            }

            $rows[$milestone] = $pb + ['url' => route('runs.show', $pb['run_id'])];
        }

        return json_encode($rows);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
