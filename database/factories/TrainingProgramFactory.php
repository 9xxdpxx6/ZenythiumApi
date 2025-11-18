<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrainingProgram>
 */
final class TrainingProgramFactory extends Factory
{
    protected $model = TrainingProgram::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->sentence(),
            'author_id' => User::factory(),
            'duration_weeks' => $this->faker->numberBetween(4, 12),
            'is_active' => true, // По умолчанию все программы активны
        ];
    }

    /**
     * Указать, что программа активна
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Указать, что программа неактивна
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Указать автора программы
     */
    public function withAuthor(?User $author = null): static
    {
        return $this->state(fn (array $attributes) => [
            'author_id' => $author?->id ?? User::factory(),
        ]);
    }

    /**
     * Указать, что программа без автора
     */
    public function withoutAuthor(): static
    {
        return $this->state(fn (array $attributes) => [
            'author_id' => null,
        ]);
    }
}


