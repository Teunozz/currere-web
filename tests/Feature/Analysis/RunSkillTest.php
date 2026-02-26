<?php

declare(strict_types=1);

use App\Ai\Agents\AnomalyDetectionAgent;
use App\Ai\Agents\HeartRateZoneAnalysisAgent;
use App\Ai\Agents\MonthlyTrainingSummaryAgent;
use App\Ai\Agents\PerformanceTrendAgent;
use App\Ai\Agents\RacePacePredictorAgent;
use App\Ai\Agents\TrainingRecommendationAgent;
use App\Models\Run;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('each skill returns expected structured output fields', function (string $skill, string $agentClass) {
    Run::factory()->for($this->user)->count(5)->create();

    $agentClass::fake();

    $this->actingAs($this->user)
        ->postJson("/analysis/{$skill}")
        ->assertSuccessful()
        ->assertJsonStructure(['data']);
})->with([
    ['monthly-summary', MonthlyTrainingSummaryAgent::class],
    ['performance-trend', PerformanceTrendAgent::class],
    ['race-pace', RacePacePredictorAgent::class],
    ['heart-rate-zones', HeartRateZoneAnalysisAgent::class],
    ['anomaly-detection', AnomalyDetectionAgent::class],
    ['training-recommendation', TrainingRecommendationAgent::class],
]);

test('insufficient data returns message when user has fewer than 3 runs', function () {
    Run::factory()->for($this->user)->count(2)->create();

    $this->actingAs($this->user)
        ->postJson('/analysis/monthly-summary')
        ->assertUnprocessable()
        ->assertJsonPath('message', 'You need at least 3 runs to use AI analysis.');
});

test('invalid skill name returns 422', function () {
    Run::factory()->for($this->user)->count(5)->create();

    $this->actingAs($this->user)
        ->postJson('/analysis/nonexistent-skill')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['skill']);
});

test('unauthenticated user gets redirected', function () {
    $this->postJson('/analysis/monthly-summary')
        ->assertUnauthorized();
});

test('ai provider error returns user-friendly message', function () {
    Run::factory()->for($this->user)->count(5)->create();

    MonthlyTrainingSummaryAgent::fake(function () {
        throw new \RuntimeException('API connection failed');
    });

    $this->actingAs($this->user)
        ->postJson('/analysis/monthly-summary')
        ->assertServiceUnavailable()
        ->assertJsonPath('message', 'Analysis temporarily unavailable. Please try again later.');
});
