<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Plan;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workout>
 */
final class WorkoutFactory extends Factory
{
    protected $model = Workout::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-1 month', 'now');
        $finishedAt = $this->faker->optional(0.7)->dateTimeBetween($startedAt, '+3 hours');
        
        return [
            'plan_id' => Plan::factory(),
            'user_id' => User::factory(),
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
        ];
    }

    /**
     * Indicate that the workout is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = $attributes['started_at'] ?? $this->faker->dateTimeBetween('-1 month', 'now');
            $finishedAt = $this->faker->dateTimeBetween($startedAt, '+3 hours');
            
            return [
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
            ];
        });
    }

    /**
     * Indicate that the workout is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = $attributes['started_at'] ?? $this->faker->dateTimeBetween('-1 month', 'now');
            
            return [
                'started_at' => $startedAt,
                'finished_at' => null,
            ];
        });
    }
}
