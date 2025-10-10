<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Cycle Model', function () {
    it('returns 0 when cycle has no plans', function () {
        $cycle = Cycle::factory()->create([
            'user_id' => $this->user->id,
            'weeks' => 6,
        ]);
        
        expect($cycle->progress_percentage)->toBe(0);
    });
    
    it('returns 0 when cycle has no weeks specified', function () {
        $cycle = Cycle::factory()->create([
            'user_id' => $this->user->id,
            'weeks' => 0, // Используем 0 вместо null
        ]);
        
        // Создаем планы
        \App\Models\Plan::factory()->count(3)->create([
            'cycle_id' => $cycle->id,
        ]);
        
        expect($cycle->progress_percentage)->toBe(0);
    });
    
    it('calculates progress percentage based on completed workouts', function () {
        $cycle = Cycle::factory()->create([
            'user_id' => $this->user->id,
            'weeks' => 6, // 6 недель
        ]);
        
        // Создаем 3 плана в цикле
        $plans = \App\Models\Plan::factory()->count(3)->create([
            'cycle_id' => $cycle->id,
        ]);
        
        // Общее количество запланированных тренировок = 6 недель × 3 плана = 18
        // Создаем 9 завершенных тренировок (50% от 18)
        for ($i = 0; $i < 9; $i++) {
            \App\Models\Workout::factory()->create([
                'plan_id' => $plans->random()->id,
                'user_id' => $this->user->id,
                'finished_at' => now(),
            ]);
        }
        
        // Прогресс должен быть 50% (9 из 18 тренировок завершены)
        expect($cycle->progress_percentage)->toBe(50);
    });
    
    it('returns 100 when all scheduled workouts are completed', function () {
        $cycle = Cycle::factory()->create([
            'user_id' => $this->user->id,
            'weeks' => 4, // 4 недели
        ]);
        
        // Создаем 3 плана в цикле
        $plans = \App\Models\Plan::factory()->count(3)->create([
            'cycle_id' => $cycle->id,
        ]);
        
        // Общее количество запланированных тренировок = 4 недели × 3 плана = 12
        // Создаем все 12 завершенных тренировок
        for ($i = 0; $i < 12; $i++) {
            \App\Models\Workout::factory()->create([
                'plan_id' => $plans->random()->id,
                'user_id' => $this->user->id,
                'finished_at' => now(),
            ]);
        }
        
        // Прогресс должен быть 100%
        expect($cycle->progress_percentage)->toBe(100);
    });
    
    it('ignores incomplete workouts when calculating progress', function () {
        $cycle = Cycle::factory()->create([
            'user_id' => $this->user->id,
            'weeks' => 2, // 2 недели
        ]);
        
        // Создаем 2 плана в цикле
        $plans = \App\Models\Plan::factory()->count(2)->create([
            'cycle_id' => $cycle->id,
        ]);
        
        // Общее количество запланированных тренировок = 2 недели × 2 плана = 4
        // Создаем 2 завершенные тренировки
        for ($i = 0; $i < 2; $i++) {
            \App\Models\Workout::factory()->create([
                'plan_id' => $plans->random()->id,
                'user_id' => $this->user->id,
                'finished_at' => now(),
            ]);
        }
        
        // Создаем 1 незавершенную тренировку (не должна влиять на прогресс)
        \App\Models\Workout::factory()->create([
            'plan_id' => $plans->first()->id,
            'user_id' => $this->user->id,
            'finished_at' => null,
        ]);
        
        // Прогресс должен быть 50% (2 из 4 запланированных тренировок завершены)
        expect($cycle->progress_percentage)->toBe(50);
    });
    
    it('returns 0 when no workouts are completed', function () {
        $cycle = Cycle::factory()->create([
            'user_id' => $this->user->id,
            'weeks' => 3, // 3 недели
        ]);
        
        // Создаем 2 плана в цикле
        $plans = \App\Models\Plan::factory()->count(2)->create([
            'cycle_id' => $cycle->id,
        ]);
        
        // Создаем только незавершенные тренировки
        for ($i = 0; $i < 3; $i++) {
            \App\Models\Workout::factory()->create([
                'plan_id' => $plans->random()->id,
                'user_id' => $this->user->id,
                'finished_at' => null,
            ]);
        }
        
        // Прогресс должен быть 0%
        expect($cycle->progress_percentage)->toBe(0);
    });
});
