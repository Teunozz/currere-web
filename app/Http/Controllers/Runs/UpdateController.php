<?php

declare(strict_types=1);

namespace App\Http\Controllers\Runs;

use App\Http\Requests\Runs\UpdateRunRequest;
use App\Http\Resources\RunResource;
use App\Models\Run;
use App\Services\Runs\UpdateRunAction;
use Illuminate\Http\JsonResponse;

class UpdateController
{
    public function __invoke(UpdateRunRequest $request, Run $run, UpdateRunAction $action): JsonResponse
    {
        $updated = $action->execute($run, $request->validated());

        return response()->json([
            'run' => new RunResource($updated),
        ]);
    }
}
