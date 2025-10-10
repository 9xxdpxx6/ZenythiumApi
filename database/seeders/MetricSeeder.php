<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Metric;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

final class MetricSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run User seeder first.');
            return;
        }

        foreach ($users as $user) {
            // Create metrics for the last 90 days
            $this->createUserMetrics($user);
        }

        $this->command->info('Metric seeder completed successfully.');
    }

    /**
     * Create realistic weight metrics for a user over time
     */
    private function createUserMetrics(User $user): void
    {
        // Starting weight (random between 60-100 kg)
        $startingWeight = rand(60, 100);
        $currentWeight = $startingWeight;
        
        // Create metrics for the last 90 days
        for ($i = 89; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            
            // Skip some days to make it more realistic (not every day has a measurement)
            if (rand(1, 3) === 1) { // 33% chance of having a measurement on any given day
                
                // Simulate realistic weight fluctuations
                $weightChange = $this->getRealisticWeightChange($i);
                $currentWeight += $weightChange;
                
                // Ensure weight stays within reasonable bounds
                $currentWeight = max(50, min(150, $currentWeight));
                
                // Add some random notes occasionally
                $note = null;
                if (rand(1, 10) === 1) { // 10% chance of having a note
                    $note = $this->getRandomNote();
                }
                
                Metric::create([
                    'user_id' => $user->id,
                    'date' => $date,
                    'weight' => round($currentWeight, 1),
                    'note' => $note,
                ]);
            }
        }
    }

    /**
     * Get realistic weight change based on time progression
     */
    private function getRealisticWeightChange(int $daysAgo): float
    {
        // Simulate different phases of weight change
        if ($daysAgo > 60) {
            // Early phase: more significant changes
            return rand(-20, 30) / 10; // -2.0 to +3.0 kg
        } elseif ($daysAgo > 30) {
            // Middle phase: moderate changes
            return rand(-15, 20) / 10; // -1.5 to +2.0 kg
        } else {
            // Recent phase: smaller fluctuations
            return rand(-10, 15) / 10; // -1.0 to +1.5 kg
        }
    }

    /**
     * Get random note for metrics
     */
    private function getRandomNote(): string
    {
        $notes = [
            'Хорошее самочувствие',
            'Чувствую себя легче',
            'После тренировки',
            'Утром натощак',
            'После еды',
            'Плохое самочувствие',
            'После кардио',
            'Вес в норме',
            'Немного прибавил',
            'Потерял вес',
            'Стабильный вес',
            'После выходных',
            'Перед тренировкой',
            'После силовой тренировки',
            'Чувствую прогресс',
            'Нужно больше тренироваться',
            'Отличная форма',
            'Усталость',
            'Много воды выпил',
            'Мало спал',
        ];

        return $notes[array_rand($notes)];
    }
}
