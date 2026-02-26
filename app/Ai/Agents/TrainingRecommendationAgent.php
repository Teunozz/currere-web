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
class TrainingRecommendationAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(private int $userId) {}

    public function instructions(): Stringable|string
    {
        return 'You are a running coach. Based on recent training load and patterns, suggest what the runner should do next (easy run, rest day, tempo run, long run, intervals). Consider weekly mileage progression, rest patterns, and training intensity. Provide specific distance and pace suggestions.';
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
            'recommendation_type' => $schema->string()->required(),
            'reasoning' => $schema->string()->required(),
            'suggested_distance_km' => $schema->number(),
            'suggested_pace_seconds_per_km' => $schema->integer(),
            'rest_days_this_week' => $schema->integer(),
            'weekly_distance_km' => $schema->number(),
            'summary_text' => $schema->string()->required(),
        ];
    }
}
