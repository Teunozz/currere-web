<?php

declare(strict_types=1);

namespace App\Http\Controllers\Runs;

use App\Http\Resources\RunResource;
use App\Models\User;
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
            'records' => $this->getPersonalRecords($request->user()),
            'averages' => $this->getAverages($request->user()),
        ]);
    }

    /**
     * @return array{longest_distance: array{run_id: int, value: float}|null, fastest_pace: array{run_id: int, value: int}|null}
     */
    private function getPersonalRecords(User $user): array
    {
        $longestDistance = $user->runs()
            ->orderByDesc('distance_km')
            ->first(['id', 'distance_km']);

        $fastestPace = $user->runs()
            ->whereNotNull('avg_pace_seconds_per_km')
            ->orderBy('avg_pace_seconds_per_km')
            ->first(['id', 'avg_pace_seconds_per_km']);

        return [
            'longest_distance' => $longestDistance
                ? ['run_id' => $longestDistance->id, 'value' => (float) $longestDistance->distance_km]
                : null,
            'fastest_pace' => $fastestPace
                ? ['run_id' => $fastestPace->id, 'value' => $fastestPace->avg_pace_seconds_per_km]
                : null,
        ];
    }

    /**
     * @return array{avg_distance_km: float|null, avg_pace_seconds_per_km: float|null, avg_heart_rate: float|null}
     */
    private function getAverages(User $user): array
    {
        $averages = $user->runs()
            ->selectRaw('ROUND(AVG(distance_km), 2) as avg_distance_km')
            ->selectRaw('ROUND(AVG(avg_pace_seconds_per_km)) as avg_pace_seconds_per_km')
            ->selectRaw('ROUND(AVG(avg_heart_rate)) as avg_heart_rate')
            ->first();

        return [
            'avg_distance_km' => $averages?->avg_distance_km ? (float) $averages->avg_distance_km : null,
            'avg_pace_seconds_per_km' => $averages?->avg_pace_seconds_per_km ? (int) $averages->avg_pace_seconds_per_km : null,
            'avg_heart_rate' => $averages?->avg_heart_rate ? (int) $averages->avg_heart_rate : null,
        ];
    }
}
