<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analysis;

use App\Http\Requests\Runs\UpdateRunRequest;
use App\Http\Resources\RunResource;
use App\Models\Run;
use App\Services\Ai\PendingActionStore;
use App\Services\Runs\UpdateRunAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ConfirmActionController
{
    public function __invoke(
        Request $request,
        string $id,
        PendingActionStore $store,
        UpdateRunAction $updateRun,
    ): JsonResponse {
        $action = $store->get($id);

        if ($action === null) {
            return response()->json([
                'message' => 'This action has expired or is invalid.',
            ], 404);
        }

        if (($action['user_id'] ?? null) !== $request->user()->id) {
            abort(403);
        }

        $run = Run::query()
            ->where('user_id', $action['user_id'])
            ->find($action['run_id'] ?? null);

        if (! $run instanceof Run) {
            $store->forget($id);

            return response()->json([
                'message' => 'The run no longer exists.',
            ], 404);
        }

        $result = match ($action['type'] ?? null) {
            'delete_run' => $this->executeDelete($run),
            'edit_run' => $this->executeEdit($run, $action['changes'] ?? [], $updateRun),
            default => throw ValidationException::withMessages([
                'action' => 'Unsupported pending action type.',
            ]),
        };

        $store->forget($id);

        return response()->json([
            'status' => 'executed',
            'result' => $result,
        ]);
    }

    /**
     * @return array{deleted_run_id: int}
     */
    private function executeDelete(Run $run): array
    {
        $deletedId = $run->id;
        $run->delete();

        return ['deleted_run_id' => $deletedId];
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array{run: RunResource}
     */
    private function executeEdit(Run $run, array $changes, UpdateRunAction $updateRun): array
    {
        Validator::make($changes, UpdateRunRequest::baseRules())->validate();

        return ['run' => new RunResource($updateRun->execute($run, $changes))];
    }
}
