<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HeartRateSample;
use App\Models\Run;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HeartRateSample> */
class HeartRateSampleFactory extends Factory
{
    protected $model = HeartRateSample::class;

    public function definition(): array
    {
        return [
            'run_id' => Run::factory(),
            'timestamp' => fake()->dateTimeBetween('-6 months', 'now'),
            'bpm' => fake()->numberBetween(80, 200),
        ];
    }
}
