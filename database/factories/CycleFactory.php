<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cycle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cycle>
 */
final class CycleFactory extends Factory
{
    protected $model = Cycle::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // По умолчанию создаем завершенные циклы из прошлого
        // (активные циклы должны быть явно указаны через active())
        $startDate = $this->faker->dateTimeBetween('-3 months', '-1 month');
        $weeks = $this->faker->numberBetween(4, 12);
        $endDate = $this->faker->dateTimeBetween(
            (clone $startDate)->modify("+{$weeks} weeks"),
            (clone $startDate)->modify("+{$weeks} weeks +1 week")
        );
        
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'weeks' => $weeks,
        ];
    }

    /**
     * Указать, что цикл активен (без даты завершения).
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = $attributes['start_date'] ?? $this->faker->dateTimeBetween('-4 weeks', 'now');
            
            return [
                'start_date' => $startDate,
                'end_date' => null,
            ];
        });
    }
}
