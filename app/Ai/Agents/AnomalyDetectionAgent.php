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
class AnomalyDetectionAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(private int $userId) {}

    public function instructions(): Stringable|string
    {
        return 'You are a running data analyst specializing in anomaly detection. Flag runs with unusual patterns: heart rate significantly higher than normal for a given pace, sudden pace drops, unusually short or long runs compared to the pattern. Provide clear explanations for each flagged run.';
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
            'flagged_runs' => $schema->array()->required(),
            'total_runs_analyzed' => $schema->integer()->required(),
            'summary_text' => $schema->string()->required(),
        ];
    }
}
