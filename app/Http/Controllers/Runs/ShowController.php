<?php

declare(strict_types=1);

namespace App\Http\Controllers\Runs;

use App\Http\Resources\RunDetailResource;
use App\Models\Run;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowController
{
    public function __invoke(Request $request, Run $run): Response
    {
        if ($run->user_id !== $request->user()->id) {
            abort(403);
        }

        $run->load(['heartRateSamples', 'paceSplits']);

        return Inertia::render('runs/Show', [
            'run' => new RunDetailResource($run),
        ]);
    }
}
