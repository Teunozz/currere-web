<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\RunDetailResource;
use App\Models\Run;
use Illuminate\Http\Request;

class ShowRunController
{
    public function __invoke(Request $request, Run $run): RunDetailResource
    {
        if ($run->user_id !== $request->user()->id) {
            abort(404);
        }

        $run->load(['heartRateSamples', 'paceSplits']);

        return new RunDetailResource($run);
    }
}
