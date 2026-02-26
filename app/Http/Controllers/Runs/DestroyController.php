<?php

declare(strict_types=1);

namespace App\Http\Controllers\Runs;

use App\Models\Run;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DestroyController
{
    public function __invoke(Request $request, Run $run): RedirectResponse
    {
        if ($run->user_id !== $request->user()->id) {
            abort(403);
        }

        $run->delete();

        return to_route('dashboard');
    }
}
