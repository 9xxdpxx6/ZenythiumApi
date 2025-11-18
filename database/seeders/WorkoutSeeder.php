<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Cycle;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

final class WorkoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users and plans
        $users = User::all();
        $plans = Plan::all();

        if ($users->isEmpty() || $plans->isEmpty()) {
            $this->command->warn('No users or plans found. Please run User and Plan seeders first.');
            return;
        }

        $workouts = [];
        $usersWithActiveWorkout = []; // Отслеживаем пользователей с активными тренировками
        $userWorkoutDates = []; // Отслеживаем даты тренировок для каждого пользователя (user_id => [даты])

        // Загружаем все циклы заранее для эффективности
        $allCycles = Cycle::with('plans')->get()->keyBy('id');
        $activeCycleIds = $allCycles->whereNull('end_date')->pluck('id')->toArray();
        
        // Вспомогательная функция для проверки и добавления даты тренировки
        $addWorkoutDate = function ($userId, $date) use (&$userWorkoutDates): bool {
            $dateKey = $date->format('Y-m-d');
            if (!isset($userWorkoutDates[$userId])) {
                $userWorkoutDates[$userId] = [];
            }
            if (in_array($dateKey, $userWorkoutDates[$userId], true)) {
                return false; // Уже есть тренировка в этот день
            }
            $userWorkoutDates[$userId][] = $dateKey;
            return true; // Дата добавлена
        };
        
        // Сначала создаем тренировки для завершенных циклов, чтобы достичь 100% прогресса
        $completedCycles = $allCycles->whereNotNull('end_date');
        foreach ($completedCycles as $cycle) {
            $cyclePlans = $cycle->plans;
            $totalPlans = $cyclePlans->count();
            
            if ($totalPlans === 0) {
                continue;
            }
            
            // Для 100% прогресса нужно: weeks * totalPlans завершенных тренировок
            $totalWorkoutsNeeded = $cycle->weeks * $totalPlans;
            
            // Распределяем тренировки по планам и неделям цикла
            $workoutIndex = 0;
            $attempts = 0;
            $maxAttempts = $totalWorkoutsNeeded * 2; // Максимум попыток
            
            for ($week = 0; $week < $cycle->weeks; $week++) {
                for ($planInWeek = 0; $planInWeek < $totalPlans; $planInWeek++) {
                    $plan = $cyclePlans->get($planInWeek);
                    if (!$plan) {
                        continue;
                    }
                    
                    // Вычисляем дату тренировки в пределах периода цикла
                    $cycleDuration = $cycle->start_date->diffInDays($cycle->end_date);
                    if ($totalWorkoutsNeeded > 1) {
                        $workoutDay = (int) ($cycleDuration * $workoutIndex / ($totalWorkoutsNeeded - 1));
                    } else {
                        $workoutDay = 0;
                    }
                    $workoutDate = $cycle->start_date->copy()->addDays($workoutDay);
                    
                    // Убеждаемся, что дата тренировки в пределах цикла
                    if ($workoutDate->greaterThan($cycle->end_date)) {
                        $workoutDate = $cycle->end_date->copy();
                    }
                    
                    // Проверяем, что в этот день у пользователя еще нет тренировки
                    // Если есть, сдвигаем на следующий день
                    while (!$addWorkoutDate($cycle->user_id, $workoutDate) && $attempts < $maxAttempts) {
                        $workoutDate->addDay();
                        // Не выходим за пределы цикла
                        if ($workoutDate->greaterThan($cycle->end_date)) {
                            break; // Не можем создать тренировку в пределах цикла
                        }
                        $attempts++;
                    }
                    
                    if ($attempts >= $maxAttempts) {
                        continue; // Пропускаем эту тренировку
                    }
                    
                    // Random workout time between 6 AM and 10 PM
                    $startTime = $workoutDate->copy()->setTime(rand(6, 22), rand(0, 59));
                    $endTime = $startTime->copy()->addMinutes(rand(45, 120));
                    
                    $workouts[] = [
                        'plan_id' => $plan->id,
                        'user_id' => $cycle->user_id,
                        'started_at' => $startTime,
                        'finished_at' => $endTime, // Завершенная тренировка для завершенного цикла
                    ];
                    
                    $workoutIndex++;
                }
            }
        }

        // Create workouts for the last 60 days (more history) для планов без циклов
        // НЕ создаем активные тренировки здесь - они будут созданы в конце с самой поздней датой
        for ($i = 0; $i < 60; $i++) {
            $date = Carbon::now()->subDays($i);
            
            // Higher chance of having workouts (50% chance instead of 33%)
            if (rand(1, 2) === 1) { // 50% chance of having a workout on any given day
                $user = $users->random();
                
                // Проверяем, что в этот день у пользователя еще нет тренировки
                if (!$addWorkoutDate($user->id, $date)) {
                    continue; // Уже есть тренировка в этот день
                }
                
                // Выбираем планы без циклов или планы из активных циклов
                $availablePlans = $plans->whereNull('cycle_id')
                    ->merge($plans->filter(function ($plan) use ($activeCycleIds) {
                        return $plan->cycle_id && in_array($plan->cycle_id, $activeCycleIds, true);
                    }));
                
                if ($availablePlans->isEmpty()) {
                    continue;
                }
                
                $plan = $availablePlans->random();
                
                // Random workout time between 6 AM and 10 PM
                $startTime = $date->copy()->setTime(rand(6, 22), rand(0, 59));
                
                // Все тренировки здесь завершены (активные будут созданы в конце с самой поздней датой)
                $endTime = $startTime->copy()->addMinutes(rand(45, 120)); // 45-120 minutes workout
                $workouts[] = [
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                    'started_at' => $startTime,
                    'finished_at' => $endTime,
                ];
            }
        }

        // Создаем активные тренировки ПОСЛЕДНИМИ (максимум по одной на пользователя)
        // Это гарантирует, что активная тренировка будет самой последней по дате
        $availableUsers = $users->filter(function ($user) use (&$usersWithActiveWorkout, &$userWorkoutDates) {
            // Проверяем, что у пользователя еще нет активной тренировки
            if (in_array($user->id, $usersWithActiveWorkout, true)) {
                return false;
            }
            // Проверяем, что сегодня у пользователя еще нет тренировки
            $today = Carbon::now()->format('Y-m-d');
            if (!isset($userWorkoutDates[$user->id])) {
                $userWorkoutDates[$user->id] = [];
            }
            if (in_array($today, $userWorkoutDates[$user->id], true)) {
                return false; // Уже есть тренировка сегодня
            }
            $userWorkoutDates[$user->id][] = $today;
            return true;
        });
        
        $activeWorkoutCount = min(3, $availableUsers->count()); // Максимум 3 активные тренировки, но не больше доступных пользователей
        
        foreach ($availableUsers->random($activeWorkoutCount) as $user) {
            // Выбираем планы без циклов или планы из активных циклов
            $availablePlans = $plans->whereNull('cycle_id')
                ->merge($plans->filter(function ($plan) use ($activeCycleIds) {
                    return $plan->cycle_id && in_array($plan->cycle_id, $activeCycleIds, true);
                }));
            
            if ($availablePlans->isEmpty()) {
                continue;
            }
            
            $plan = $availablePlans->random();
            // Активная тренировка - начата недавно (10-120 минут назад)
            // Это будет самая поздняя дата, так как создается последней
            $startTime = Carbon::now()->subMinutes(rand(10, 120));
            
            $workouts[] = [
                'plan_id' => $plan->id,
                'user_id' => $user->id,
                'started_at' => $startTime,
                'finished_at' => null, // Ongoing workout
            ];
            
            $usersWithActiveWorkout[] = $user->id; // Помечаем, что у пользователя есть активная тренировка
        }

        foreach ($workouts as $workoutData) {
            Workout::create($workoutData);
        }

        $this->command->info('Workout seeder completed successfully.');
    }
}
