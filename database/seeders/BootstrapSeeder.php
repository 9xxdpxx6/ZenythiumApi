<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class BootstrapSeeder extends Seeder
{
    /**
     * Seed the application's database with essential data only.
     * This seeder contains only the minimum required data for the application to function.
     */
    public function run(): void
    {
        $this->command->info('Starting bootstrap seeding...');

        // Create a basic user for the application
        $this->createBasicUser();

        // Seed essential data
        $this->call([
            MuscleGroupSeeder::class,
            ExerciseSeeder::class,
        ]);

        $this->command->info('Bootstrap seeding completed successfully.');
        $this->command->info('Essential data has been seeded:');
        $this->command->info('- Basic user account');
        $this->command->info('- Muscle groups');
        $this->command->info('- Exercises');
    }

    /**
     * Create a basic user account for the application.
     */
    private function createBasicUser(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@zenythium.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('Zxcvbnm228'),
            ]
        );

        $this->command->info("Basic user created: {$user->email}");
    }
}
