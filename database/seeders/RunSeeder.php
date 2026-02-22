<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HeartRateSample;
use App\Models\PaceSplit;
use App\Models\Run;
use App\Models\User;
use Illuminate\Database\Seeder;

class RunSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            return;
        }

        Run::factory()
            ->count(20)
            ->for($user)
            ->create()
            ->each(function (Run $run): void {
                $durationMinutes = (int) round($run->duration_seconds / 60);
                $sampleCount = min($durationMinutes * 6, 500);

                HeartRateSample::factory()
                    ->count($sampleCount)
                    ->for($run)
                    ->sequence(fn ($sequence) => [
                        'timestamp' => $run->start_time->copy()->addSeconds((int) ($sequence->index * ($run->duration_seconds / max($sampleCount, 1)))),
                        'bpm' => fake()->numberBetween(
                            max(100, $run->avg_heart_rate - 30),
                            min(200, $run->avg_heart_rate + 20),
                        ),
                    ])
                    ->create();

                $fullKm = (int) floor((float) $run->distance_km);
                $remainderKm = (float) $run->distance_km - $fullKm;

                for ($km = 1; $km <= $fullKm; $km++) {
                    PaceSplit::factory()
                        ->for($run)
                        ->create([
                            'kilometer_number' => $km,
                            'split_time_seconds' => fake()->numberBetween(
                                max(180, $run->avg_pace_seconds_per_km - 30),
                                $run->avg_pace_seconds_per_km + 30,
                            ),
                            'pace_seconds_per_km' => fake()->numberBetween(
                                max(180, $run->avg_pace_seconds_per_km - 30),
                                $run->avg_pace_seconds_per_km + 30,
                            ),
                            'is_partial' => false,
                            'partial_distance_km' => null,
                        ]);
                }

                if ($remainderKm > 0.05) {
                    PaceSplit::factory()
                        ->for($run)
                        ->partial(round($remainderKm, 3))
                        ->create([
                            'kilometer_number' => $fullKm + 1,
                            'split_time_seconds' => (int) round($remainderKm * $run->avg_pace_seconds_per_km),
                            'pace_seconds_per_km' => $run->avg_pace_seconds_per_km,
                        ]);
                }
            });
    }
}
