<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Metric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Metric>
 */
final class MetricFactory extends Factory
{
    protected $model = Metric::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'weight' => $this->faker->randomFloat(2, 50, 120), // Weight between 50-120 kg
            'note' => $this->faker->optional(0.3)->sentence(), // 30% chance of having a note
        ];
    }

    /**
     * Create a metric with specific weight range.
     */
    public function withWeightRange(float $min, float $max): static
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $this->faker->randomFloat(2, $min, $max),
        ]);
    }

    /**
     * Create a metric for a specific date.
     */
    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date,
        ]);
    }

    /**
     * Create a metric for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a metric with a note.
     */
    public function withNote(string $note = null): static
    {
        return $this->state(fn (array $attributes) => [
            'note' => $note ?? $this->faker->sentence(),
        ]);
    }

    /**
     * Create a metric without a note.
     */
    public function withoutNote(): static
    {
        return $this->state(fn (array $attributes) => [
            'note' => null,
        ]);
    }

    /**
     * Create a recent metric (within last 30 days).
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Create an old metric (older than 30 days).
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $this->faker->dateTimeBetween('-1 year', '-30 days'),
        ]);
    }
}