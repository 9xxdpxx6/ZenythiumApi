<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Workout;
use App\Models\PlanExercise;
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
            'weight' => $this->faker->randomFloat(2, 20, 200), // Weight between 20-200 kg
            'reps' => $this->faker->numberBetween(1, 20), // Reps between 1-20
        ];
    }

    /**
     * Create a workout set with specific weight range.
     */
    public function withWeightRange(float $min, float $max): static
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $this->faker->randomFloat(2, $min, $max),
        ]);
    }

    /**
     * Create a workout set with specific rep range.
     */
    public function withRepRange(int $min, int $max): static
    {
        return $this->state(fn (array $attributes) => [
            'reps' => $this->faker->numberBetween($min, $max),
        ]);
    }

    /**
     * Create a light workout set (low weight, high reps).
     */
    public function light(): static
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $this->faker->randomFloat(2, 10, 50),
            'reps' => $this->faker->numberBetween(12, 20),
        ]);
    }

    /**
     * Create a heavy workout set (high weight, low reps).
     */
    public function heavy(): static
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $this->faker->randomFloat(2, 80, 200),
            'reps' => $this->faker->numberBetween(1, 6),
        ]);
    }

    /**
     * Create a moderate workout set (medium weight, medium reps).
     */
    public function moderate(): static
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $this->faker->randomFloat(2, 40, 100),
            'reps' => $this->faker->numberBetween(6, 12),
        ]);
    }
}