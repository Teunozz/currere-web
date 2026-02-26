<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\FetchRunStatsTool;
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
class RacePacePredictorAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(private int $userId) {}

    public function instructions(): Stringable|string
    {
        return 'You are a race performance predictor. Based on recent training data, estimate finish times for common race distances (5K, 10K, half marathon, marathon). Use the Riegel formula (T2 = T1 × (D2/D1)^1.06) as a baseline, adjusted by training patterns. Provide confidence levels and explanations for each prediction.';
    }

    public function tools(): iterable
    {
        return [
            new FetchRunStatsTool($this->userId),
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'predictions' => $schema->array()->required(),
            'base_distance_km' => $schema->number()->required(),
            'base_time_seconds' => $schema->integer()->required(),
            'explanation_text' => $schema->string()->required(),
        ];
    }
}
