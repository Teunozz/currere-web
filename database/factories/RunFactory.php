<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Run;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Run> */
class RunFactory extends Factory
{
    protected $model = Run::class;

    public function definition(): array
    {
        $distanceKm = fake()->randomFloat(3, 2, 42);
        $paceSecondsPerKm = fake()->numberBetween(210, 420);
        $durationSeconds = (int) round($distanceKm * $paceSecondsPerKm);
        $startTime = fake()->dateTimeBetween('-6 months', 'now');
        $endTime = (clone $startTime)->modify("+{$durationSeconds} seconds");

        return [
            'user_id' => User::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'distance_km' => $distanceKm,
            'duration_seconds' => $durationSeconds,
            'steps' => fake()->numberBetween((int) ($distanceKm * 1000), (int) ($distanceKm * 1600)),
            'avg_heart_rate' => fake()->numberBetween(120, 185),
            'avg_pace_seconds_per_km' => $paceSecondsPerKm,
        ];
    }
}
