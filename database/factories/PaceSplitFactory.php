<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PaceSplit;
use App\Models\Run;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaceSplit> */
class PaceSplitFactory extends Factory
{
    protected $model = PaceSplit::class;

    public function definition(): array
    {
        return [
            'run_id' => Run::factory(),
            'kilometer_number' => fake()->numberBetween(1, 42),
            'split_time_seconds' => fake()->numberBetween(180, 480),
            'pace_seconds_per_km' => fake()->numberBetween(210, 420),
            'is_partial' => false,
            'partial_distance_km' => null,
        ];
    }

    public function partial(float $distance = 0.5): static
    {
        return $this->state(fn (array $attributes) => [
            'is_partial' => true,
            'partial_distance_km' => $distance,
        ]);
    }
}
