<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\ComparePeriodsTool;
use App\Ai\Tools\FetchHeartRateDataTool;
use App\Ai\Tools\FetchPersonalBestsTool;
use App\Ai\Tools\FetchRunDetailTool;
use App\Ai\Tools\FetchRunStatsTool;
use App\Ai\Tools\FetchRunsTool;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\UseSmartestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Anthropic)]
#[UseSmartestModel]
class RunCoachAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * @param  iterable<\Laravel\Ai\Messages\Message>  $messages
     */
    public function __construct(
        private int $userId,
        private iterable $messages = [],
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are a personal running coach with direct access to the athlete's training data through tools. Always reach for a tool before making a quantitative claim — never invent distances, paces, heart rates, or dates.

Tools available:
- fetch_runs: list runs in a window with optional distance / pace / heart-rate filters
- fetch_run_stats: aggregated totals and averages over a window
- fetch_heart_rate_data: heart-rate detail and time-in-zone estimates
- fetch_run_detail: a single run including HR samples and pace splits
- compare_periods: side-by-side aggregates for two date ranges
- fetch_personal_bests: PBs at 1K / 5K / 10K / HM / M

Citation rule: whenever a tool result includes a `url` field for a specific run, link that run in your reply using markdown like `[YYYY-MM-DD — 8.2 km](url)`. Cite every specific run you reference. If a claim isn't backed by a tool result, say so plainly instead of guessing.

Keep responses conversational and direct — short paragraphs, no headings unless the user asks for them. Offer concrete observations and one actionable suggestion when relevant.
PROMPT;
    }

    public function tools(): iterable
    {
        return [
            new FetchRunsTool($this->userId),
            new FetchRunStatsTool($this->userId),
            new FetchHeartRateDataTool($this->userId),
            new FetchRunDetailTool($this->userId),
            new ComparePeriodsTool($this->userId),
            new FetchPersonalBestsTool($this->userId),
        ];
    }

    public function messages(): iterable
    {
        return $this->messages;
    }
}
