<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Starter Prompts
    |--------------------------------------------------------------------------
    |
    | These prompts seed the input on the AI Analysis chat page when an empty
    | conversation's starter pills are clicked. Each entry has a short label
    | shown on the pill and a longer prompt that gets dropped into the input.
    |
    */

    'starter_prompts' => [
        [
            'id' => 'monthly-summary',
            'label' => 'Last month at a glance',
            'prompt' => 'Summarize my last month of running — distance, time, runs, and how it compares to the month before.',
        ],
        [
            'id' => 'performance-trend',
            'label' => 'Am I getting faster?',
            'prompt' => 'Look at my pace over the last 8 weeks. Am I trending faster or slower?',
        ],
        [
            'id' => 'race-pace',
            'label' => 'Predict my race times',
            'prompt' => 'Based on my recent training, what could I realistically run for 5K, 10K, half marathon, and marathon?',
        ],
        [
            'id' => 'heart-rate-zones',
            'label' => 'Heart-rate balance',
            'prompt' => 'How is my heart-rate zone distribution looking over the last month? Am I doing enough easy running?',
        ],
        [
            'id' => 'anomaly-detection',
            'label' => 'Anything unusual?',
            'prompt' => 'Are any of my recent runs unusual — heart rate too high, pace off, anything worth flagging?',
        ],
        [
            'id' => 'training-recommendation',
            'label' => 'What should I run today?',
            'prompt' => 'Given my last two weeks, what kind of run should I do today — easy, tempo, long, or rest?',
        ],
    ],

];
