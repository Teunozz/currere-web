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
        return Inertia::render('analysis/Index', [
            'starterPrompts' => config('analysis.starter_prompts'),
            'runCount' => $request->user()->runs()->count(),
        ]);
    }
}
