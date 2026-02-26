<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\RunResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IndexRunController
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->query('per_page', '15'), 100);

        $runs = $request->user()
            ->runs()
            ->orderByDesc('start_time')
            ->paginate($perPage);

        return RunResource::collection($runs);
    }
}
