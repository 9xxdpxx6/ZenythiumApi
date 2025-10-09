<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\Plan;
use App\Models\PlanExercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlanExercise>
 */
final class PlanExerciseFactory extends Factory
{
    protected $model = PlanExercise::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'exercise_id' => Exercise::factory(),
            'order' => $this->faker->numberBetween(1, 20),
        ];
    }
}
