<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analysis;

use App\Ai\Agents\AnomalyDetectionAgent;
use App\Ai\Agents\HeartRateZoneAnalysisAgent;
use App\Ai\Agents\MonthlyTrainingSummaryAgent;
use App\Ai\Agents\PerformanceTrendAgent;
use App\Ai\Agents\RacePacePredictorAgent;
use App\Ai\Agents\TrainingRecommendationAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunSkillController
{
    /** @var array<string, class-string> */
    private const SKILL_MAP = [
        'monthly-summary' => MonthlyTrainingSummaryAgent::class,
        'performance-trend' => PerformanceTrendAgent::class,
        'race-pace' => RacePacePredictorAgent::class,
        'heart-rate-zones' => HeartRateZoneAnalysisAgent::class,
        'anomaly-detection' => AnomalyDetectionAgent::class,
        'training-recommendation' => TrainingRecommendationAgent::class,
    ];

    public function __invoke(Request $request, string $skill): JsonResponse
    {
        if (! array_key_exists($skill, self::SKILL_MAP)) {
            return response()->json([
                'message' => 'The selected skill is invalid.',
                'errors' => ['skill' => ['The selected skill is invalid.']],
            ], 422);
        }

        $user = $request->user();
        $runCount = $user->runs()->count();

        if ($runCount < 3) {
            return response()->json([
                'message' => 'You need at least 3 runs to use AI analysis.',
            ], 422);
        }

        try {
            $agentClass = self::SKILL_MAP[$skill];
            $agent = new $agentClass($user->id);
            $response = $agent->prompt('Analyze my recent running data and provide insights.');

            return response()->json([
                'data' => $response->toArray(),
                'skill' => $skill,
            ]);
        } catch (Throwable $e) {
            Log::error('AI skill execution failed', [
                'skill' => $skill,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Analysis temporarily unavailable. Please try again later.',
            ], 503);
        }
    }
}
