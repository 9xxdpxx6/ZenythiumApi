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
        $startDate = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $endDate = $this->faker->dateTimeBetween($startDate, '+2 months');
        
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'weeks' => $this->faker->numberBetween(4, 12),
        ];
    }
}
