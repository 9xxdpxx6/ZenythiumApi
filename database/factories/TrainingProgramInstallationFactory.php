<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cycle;
use App\Models\TrainingProgram;
use App\Models\TrainingProgramInstallation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrainingProgramInstallation>
 */
final class TrainingProgramInstallationFactory extends Factory
{
    protected $model = TrainingProgramInstallation::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'training_program_id' => TrainingProgram::factory(),
            'installed_cycle_id' => null,
        ];
    }

    /**
     * Указать пользователя
     */
    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    /**
     * Указать программу
     */
    public function forProgram(?TrainingProgram $program = null): static
    {
        return $this->state(fn (array $attributes) => [
            'training_program_id' => $program?->id ?? TrainingProgram::factory(),
        ]);
    }

    /**
     * Указать установленный цикл
     */
    public function withCycle(?Cycle $cycle = null): static
    {
        return $this->state(fn (array $attributes) => [
            'installed_cycle_id' => $cycle?->id ?? Cycle::factory(),
        ]);
    }

    /**
     * Указать, что цикл не установлен
     */
    public function withoutCycle(): static
    {
        return $this->state(fn (array $attributes) => [
            'installed_cycle_id' => null,
        ]);
    }
}



