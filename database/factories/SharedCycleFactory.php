<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cycle;
use App\Models\SharedCycle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SharedCycle>
 */
final class SharedCycleFactory extends Factory
{
    protected $model = SharedCycle::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'cycle_id' => Cycle::factory(),
            'share_id' => (string) Str::uuid(),
            'view_count' => 0,
            'import_count' => 0,
            'is_active' => true,
            'expires_at' => null,
        ];
    }

    /**
     * Указать цикл
     */
    public function forCycle(?Cycle $cycle = null): static
    {
        return $this->state(fn (array $attributes) => [
            'cycle_id' => $cycle?->id ?? Cycle::factory(),
        ]);
    }

    /**
     * Указать, что ссылка неактивна
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Указать срок действия ссылки
     */
    public function expiresAt(?\DateTimeInterface $date = null): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $date ?? now()->addDays(7),
        ]);
    }

    /**
     * Указать, что ссылка истекла
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
