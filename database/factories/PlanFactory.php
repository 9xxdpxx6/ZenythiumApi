<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cycle;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
final class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'cycle_id' => Cycle::factory(),
            'name' => $this->faker->words(3, true),
            'order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
