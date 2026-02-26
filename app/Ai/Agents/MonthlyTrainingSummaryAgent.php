<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\FetchRecentRunsTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Anthropic)]
#[UseCheapestModel]
class MonthlyTrainingSummaryAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(private int $userId) {}

    public function instructions(): Stringable|string
    {
        return 'You are a running coach analyzing training data. Summarize the last 30 days of training: total distance, total time, number of runs, average pace. Compare with the previous 30-day period. Provide a brief natural language summary highlighting key observations.';
    }

    public function tools(): iterable
    {
        return [
            new FetchRecentRunsTool($this->userId),
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'total_distance_km' => $schema->number()->required(),
            'total_time_seconds' => $schema->integer()->required(),
            'run_count' => $schema->integer()->required(),
            'avg_pace_seconds_per_km' => $schema->integer()->required(),
            'previous_month_distance_km' => $schema->number()->required(),
            'previous_month_run_count' => $schema->integer()->required(),
            'distance_change_percent' => $schema->number()->required(),
            'summary_text' => $schema->string()->required(),
        ];
    }
}
