<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analysis;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController
{
    public function __invoke(Request $request): Response
    {
        $runCount = $request->user()->runs()->count();

        $skills = [
            [
                'id' => 'monthly-summary',
                'name' => 'Monthly Training Summary',
                'description' => 'Total distance, time, runs, and comparison with last month.',
                'icon' => 'calendar',
            ],
            [
                'id' => 'performance-trend',
                'name' => 'Performance Trend',
                'description' => 'Are you getting faster or slower? Pace trends over weeks.',
                'icon' => 'trending-up',
            ],
            [
                'id' => 'race-pace',
                'name' => 'Race Pace Predictor',
                'description' => 'Estimated finish times for 5K, 10K, half marathon, marathon.',
                'icon' => 'trophy',
            ],
            [
                'id' => 'heart-rate-zones',
                'name' => 'Heart Rate Zone Analysis',
                'description' => 'Time spent in each HR zone and training balance.',
                'icon' => 'heart-pulse',
            ],
            [
                'id' => 'anomaly-detection',
                'name' => 'Anomaly Detection',
                'description' => 'Flags runs with unusual heart rate or pace patterns.',
                'icon' => 'alert-triangle',
            ],
            [
                'id' => 'training-recommendation',
                'name' => 'Training Recommendation',
                'description' => 'What to do next: easy run, rest day, tempo, or long run.',
                'icon' => 'brain',
            ],
        ];

        return Inertia::render('analysis/Index', [
            'skills' => $skills,
            'runCount' => $runCount,
        ]);
    }
}
