<?php

declare(strict_types=1);

use App\Models\PaceSplit;
use App\Models\Run;
use App\Models\User;

test('authenticated user sees runs page with paginated data', function () {
    $user = User::factory()->create();
    Run::factory()->for($user)->count(3)->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('runs/Index')
            ->has('runs.data', 3)
        );
});

test('runs are filtered by date range', function () {
    $user = User::factory()->create();

    Run::factory()->for($user)->create(['start_time' => '2026-01-15T08:00:00Z']);
    Run::factory()->for($user)->create(['start_time' => '2026-02-10T08:00:00Z']);
    Run::factory()->for($user)->create(['start_time' => '2026-02-20T08:00:00Z']);

    $this->actingAs($user)
        ->get('/dashboard?date_from=2026-02-01&date_to=2026-02-28')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('runs/Index')
            ->has('runs.data', 2)
        );
});

test('runs can be sorted by column', function () {
    $user = User::factory()->create();

    Run::factory()->for($user)->create(['distance_km' => 5.0]);
    Run::factory()->for($user)->create(['distance_km' => 15.0]);
    Run::factory()->for($user)->create(['distance_km' => 10.0]);

    $response = $this->actingAs($user)
        ->get('/dashboard?sort=distance_km&direction=desc');

    $response->assertSuccessful();

    $runs = $response->original->getData()['page']['props']['runs']['data'];
    $distances = array_column($runs, 'distance_km');
    expect($distances)->toEqual([15.0, 10.0, 5.0]);
});

test('unauthenticated users are redirected to login', function () {
    $this->get('/dashboard')
        ->assertRedirect('/login');
});

test('user only sees their own runs', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Run::factory()->for($user)->count(2)->create();
    Run::factory()->for($otherUser)->count(3)->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('runs.data', 2)
        );
});

test('records return correct run IDs and values', function () {
    $user = User::factory()->create();

    $longest = Run::factory()->for($user)->create(['distance_km' => 42.195]);
    $fastest = Run::factory()->for($user)->create(['avg_pace_seconds_per_km' => 210]);

    // Create a "normal" run that shouldn't hold any records
    Run::factory()->for($user)->create([
        'distance_km' => 5.0,
        'avg_pace_seconds_per_km' => 360,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('records.longest_distance.run_id', $longest->id)
            ->where('records.longest_distance.value', 42.195)
            ->where('records.fastest_pace.run_id', $fastest->id)
            ->where('records.fastest_pace.value', 210)
        );
});

test('records are computed across all runs ignoring date filters', function () {
    $user = User::factory()->create();

    // Record run outside the date filter range
    $longest = Run::factory()->for($user)->create([
        'start_time' => '2025-06-01T08:00:00Z',
        'distance_km' => 42.195,
    ]);

    // Normal run inside the date filter range
    Run::factory()->for($user)->create([
        'start_time' => '2026-02-10T08:00:00Z',
        'distance_km' => 5.0,
    ]);

    $this->actingAs($user)
        ->get('/dashboard?date_from=2026-02-01&date_to=2026-02-28')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('runs.data', 1)
            ->where('records.longest_distance.run_id', $longest->id)
            ->where('records.longest_distance.value', 42.195)
        );
});

test('averages are returned correctly', function () {
    $user = User::factory()->create();

    Run::factory()->for($user)->create([
        'distance_km' => 10.0,
        'avg_pace_seconds_per_km' => 300,
        'avg_heart_rate' => 150,
    ]);
    Run::factory()->for($user)->create([
        'distance_km' => 20.0,
        'avg_pace_seconds_per_km' => 360,
        'avg_heart_rate' => 170,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('averages.avg_distance_km', 15)
            ->where('averages.avg_pace_seconds_per_km', 330)
            ->where('averages.avg_heart_rate', 160)
        );
});

test('records and averages are null when user has no runs', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('records.longest_distance', null)
            ->where('records.fastest_pace', null)
            ->where('records.highest_heart_rate', null)
            ->where('averages.avg_distance_km', null)
            ->where('averages.avg_pace_seconds_per_km', null)
            ->where('averages.avg_heart_rate', null)
            ->where('totals.total_distance_km', 0)
            ->where('totals.total_duration_seconds', 0)
            ->where('totals.total_runs', 0)
        );
});

test('runs with null pace are excluded from fastest pace record', function () {
    $user = User::factory()->create();

    Run::factory()->for($user)->create([
        'distance_km' => 10.0,
        'avg_pace_seconds_per_km' => null,
    ]);

    $withPace = Run::factory()->for($user)->create([
        'distance_km' => 5.0,
        'avg_pace_seconds_per_km' => 300,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('records.fastest_pace.run_id', $withPace->id)
        );
});

test('totals are computed correctly', function () {
    $user = User::factory()->create();

    Run::factory()->for($user)->create([
        'distance_km' => 10.5,
        'duration_seconds' => 3600,
    ]);
    Run::factory()->for($user)->create([
        'distance_km' => 5.25,
        'duration_seconds' => 1800,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('totals.total_distance_km', 15.75)
            ->where('totals.total_duration_seconds', 5400)
            ->where('totals.total_runs', 2)
        );
});

test('totals ignore date filters', function () {
    $user = User::factory()->create();

    Run::factory()->for($user)->create([
        'start_time' => '2025-06-01T08:00:00Z',
        'distance_km' => 10.0,
        'duration_seconds' => 3600,
    ]);
    Run::factory()->for($user)->create([
        'start_time' => '2026-02-10T08:00:00Z',
        'distance_km' => 5.0,
        'duration_seconds' => 1800,
    ]);

    $this->actingAs($user)
        ->get('/dashboard?date_from=2026-02-01&date_to=2026-02-28')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('runs.data', 1)
            ->where('totals.total_distance_km', 15)
            ->where('totals.total_duration_seconds', 5400)
            ->where('totals.total_runs', 2)
        );
});

test('pace trend produces cumulative buckets for runs with enough splits', function () {
    $user = User::factory()->create();

    $run = Run::factory()->for($user)->create([
        'start_time' => '2026-01-10T08:00:00Z',
        'distance_km' => 12.5,
        'avg_pace_seconds_per_km' => 300,
    ]);

    // Create 12 full km splits
    foreach (range(1, 12) as $km) {
        PaceSplit::factory()->for($run)->create([
            'kilometer_number' => $km,
            'pace_seconds_per_km' => 300,
            'is_partial' => false,
        ]);
    }

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('paceTrend', 2)
            ->where('paceTrend.0.bucket_km', 5)
            ->where('paceTrend.0.pace', 300)
            ->where('paceTrend.1.bucket_km', 10)
            ->where('paceTrend.1.pace', 300)
        );
});

test('pace trend excludes partial splits from bucket computation', function () {
    $user = User::factory()->create();

    $run = Run::factory()->for($user)->create([
        'start_time' => '2026-01-10T08:00:00Z',
        'distance_km' => 5.5,
        'avg_pace_seconds_per_km' => 300,
    ]);

    // Only 4 full splits + 1 partial at km 5
    foreach (range(1, 4) as $km) {
        PaceSplit::factory()->for($run)->create([
            'kilometer_number' => $km,
            'pace_seconds_per_km' => 300,
            'is_partial' => false,
        ]);
    }
    PaceSplit::factory()->for($run)->partial()->create([
        'kilometer_number' => 5,
        'pace_seconds_per_km' => 300,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('paceTrend', 0)
        );
});

test('pace trend data is ordered chronologically', function () {
    $user = User::factory()->create();

    $olderRun = Run::factory()->for($user)->create([
        'start_time' => '2026-01-01T08:00:00Z',
        'distance_km' => 6.0,
        'avg_pace_seconds_per_km' => 350,
    ]);
    $newerRun = Run::factory()->for($user)->create([
        'start_time' => '2026-02-01T08:00:00Z',
        'distance_km' => 6.0,
        'avg_pace_seconds_per_km' => 300,
    ]);

    foreach ([$olderRun, $newerRun] as $run) {
        foreach (range(1, 5) as $km) {
            PaceSplit::factory()->for($run)->create([
                'kilometer_number' => $km,
                'pace_seconds_per_km' => $run->avg_pace_seconds_per_km,
                'is_partial' => false,
            ]);
        }
    }

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('paceTrend', 2)
            ->where('paceTrend.0.pace', 350)
            ->where('paceTrend.1.pace', 300)
        );
});

test('pace trend excludes runs with null avg pace', function () {
    $user = User::factory()->create();

    $run = Run::factory()->for($user)->create([
        'distance_km' => 6.0,
        'avg_pace_seconds_per_km' => null,
    ]);

    foreach (range(1, 5) as $km) {
        PaceSplit::factory()->for($run)->create([
            'kilometer_number' => $km,
            'pace_seconds_per_km' => 300,
            'is_partial' => false,
        ]);
    }

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('paceTrend', 0)
        );
});

test('pace trend ignores date filters', function () {
    $user = User::factory()->create();

    $run = Run::factory()->for($user)->create([
        'start_time' => '2025-06-01T08:00:00Z',
        'distance_km' => 6.0,
        'avg_pace_seconds_per_km' => 300,
    ]);

    foreach (range(1, 5) as $km) {
        PaceSplit::factory()->for($run)->create([
            'kilometer_number' => $km,
            'pace_seconds_per_km' => 300,
            'is_partial' => false,
        ]);
    }

    $this->actingAs($user)
        ->get('/dashboard?date_from=2026-02-01&date_to=2026-02-28')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('runs.data', 0)
            ->has('paceTrend', 1)
            ->where('paceTrend.0.bucket_km', 5)
        );
});
