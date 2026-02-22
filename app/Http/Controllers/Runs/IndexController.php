<?php

declare(strict_types=1);

namespace App\Http\Controllers\Runs;

use App\Http\Resources\RunResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController
{
    private const ALLOWED_SORT_COLUMNS = [
        'start_time',
        'distance_km',
        'duration_seconds',
        'avg_pace_seconds_per_km',
        'avg_heart_rate',
    ];

    public function __invoke(Request $request): Response
    {
        $query = $request->user()->runs();

        if ($request->filled('date_from')) {
            $query->where('start_time', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('start_time', '<=', $request->query('date_to').' 23:59:59');
        }

        $sortColumn = $request->query('sort', 'start_time');
        $sortDirection = $request->query('direction', 'desc');

        if (! in_array($sortColumn, self::ALLOWED_SORT_COLUMNS, true)) {
            $sortColumn = 'start_time';
        }

        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        $runs = $query->orderBy($sortColumn, $sortDirection)->paginate(15)->withQueryString();

        return Inertia::render('runs/Index', [
            'runs' => RunResource::collection($runs),
            'filters' => [
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
                'sort' => $sortColumn,
                'direction' => $sortDirection,
            ],
        ]);
    }
}
