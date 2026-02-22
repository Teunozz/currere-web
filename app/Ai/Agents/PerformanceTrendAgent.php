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
class PerformanceTrendAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(private int $userId) {}

    public function instructions(): Stringable|string
    {
        return 'You are a running performance analyst. Analyze pace trends over the last 4-12 weeks. Determine if the runner is getting faster or slower. Identify personal record distances. Provide chart-friendly data points showing weekly average pace.';
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
            'trend_direction' => $schema->string()->required(),
            'pace_change_per_week' => $schema->number()->required(),
            'weeks_analyzed' => $schema->integer()->required(),
            'data_points' => $schema->array()->required(),
            'personal_records' => $schema->array()->required(),
            'analysis_text' => $schema->string()->required(),
        ];
    }
}
