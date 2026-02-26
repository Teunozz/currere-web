<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\FetchHeartRateDataTool;
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
class HeartRateZoneAnalysisAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(private int $userId) {}

    public function instructions(): Stringable|string
    {
        return 'You are a heart rate training analyst. Analyze time spent in HR zones across recent runs using the max observed HR to calculate zones. Standard zones: Zone 1 (50-60% max HR), Zone 2 (60-70%), Zone 3 (70-80%), Zone 4 (80-90%), Zone 5 (90-100%). Determine if training is balanced, too easy, or too intense. Provide recommendations.';
    }

    public function tools(): iterable
    {
        return [
            new FetchHeartRateDataTool($this->userId),
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'max_heart_rate' => $schema->integer()->required(),
            'zones' => $schema->array()->required(),
            'total_training_seconds' => $schema->integer()->required(),
            'recommendation_text' => $schema->string()->required(),
        ];
    }
}
