<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Metric;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Metric>
 */
final class MetricFactory extends Factory
{
    protected $model = Metric::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'weight' => $this->faker->randomFloat(2, 50, 120),
            'note' => $this->faker->optional(0.7)->sentence(),
        ];
    }

    /**
     * Indicate that the metric is recent.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the metric is old.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $this->faker->dateTimeBetween('-1 year', '-6 months')->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the metric has a specific weight range.
     */
    public function weightRange(float $min, float $max): static
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $this->faker->randomFloat(2, $min, $max),
        ]);
    }

    /**
     * Indicate that the metric has no note.
     */
    public function withoutNote(): static
    {
        return $this->state(fn (array $attributes) => [
            'note' => null,
        ]);
    }
}
