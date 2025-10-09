<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlanExercise;
use App\Models\Workout;
use App\Models\WorkoutSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkoutSet>
 */
final class WorkoutSetFactory extends Factory
{
    protected $model = WorkoutSet::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'workout_id' => Workout::factory(),
            'plan_exercise_id' => PlanExercise::factory(),
            'weight' => $this->faker->optional(0.8)->randomFloat(2, 10, 200),
            'reps' => $this->faker->optional(0.8)->numberBetween(1, 50),
        ];
    }

    /**
     * Indicate that the workout set has weight and reps.
     */
    public function withData(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'weight' => $this->faker->randomFloat(2, 10, 200),
                'reps' => $this->faker->numberBetween(1, 50),
            ];
        });
    }

    /**
     * Indicate that the workout set has no weight or reps.
     */
    public function empty(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'weight' => null,
                'reps' => null,
            ];
        });
    }
}
