<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
    
    $this->cycle = Cycle::factory()->create([
        'user_id' => $this->user->id,
        'start_date' => now()->subWeeks(4),
        'end_date' => null, // Активный цикл
    ]);
    
    // Создаем 4 плана в цикле
    $this->plan1 = Plan::factory()->create([
        'cycle_id' => $this->cycle->id,
        'name' => 'План 1',
        'order' => 1,
        'is_active' => true,
    ]);
    
    $this->plan2 = Plan::factory()->create([
        'cycle_id' => $this->cycle->id,
        'name' => 'План 2',
        'order' => 2,
        'is_active' => true,
    ]);
    
    $this->plan3 = Plan::factory()->create([
        'cycle_id' => $this->cycle->id,
        'name' => 'План 3',
        'order' => 3,
        'is_active' => true,
    ]);
    
    $this->plan4 = Plan::factory()->create([
        'cycle_id' => $this->cycle->id,
        'name' => 'План 4',
        'order' => 4,
        'is_active' => true,
    ]);
});

test('training streak counts consecutive workouts without gaps in cycles', function () {
    // Неделя 1: выполнены все 4 плана (streak = 4, по +1 за каждую тренировку)
    $week1Start = now()->subWeeks(3);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan1->id,
        'started_at' => $week1Start->copy()->addDays(1),
        'finished_at' => $week1Start->copy()->addDays(1)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan2->id,
        'started_at' => $week1Start->copy()->addDays(2),
        'finished_at' => $week1Start->copy()->addDays(2)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan3->id,
        'started_at' => $week1Start->copy()->addDays(3),
        'finished_at' => $week1Start->copy()->addDays(3)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan4->id,
        'started_at' => $week1Start->copy()->addDays(4),
        'finished_at' => $week1Start->copy()->addDays(4)->addMinutes(60),
    ]);

    // Неделя 2: выполнены только 3 плана (пропуск - streak сбрасывается до 0)
    $week2Start = $week1Start->copy()->addDays(7);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan1->id,
        'started_at' => $week2Start->copy()->addDays(1),
        'finished_at' => $week2Start->copy()->addDays(1)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan2->id,
        'started_at' => $week2Start->copy()->addDays(2),
        'finished_at' => $week2Start->copy()->addDays(2)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan3->id,
        'started_at' => $week2Start->copy()->addDays(3),
        'finished_at' => $week2Start->copy()->addDays(3)->addMinutes(60),
    ]);
    // План 4 пропущен - это пропуск, streak сбрасывается

    // Неделя 3: выполнены все 4 плана (streak = 4, начинается с 0 после сброса)
    $week3Start = $week2Start->copy()->addDays(7);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan1->id,
        'started_at' => $week3Start->copy()->addDays(1),
        'finished_at' => $week3Start->copy()->addDays(1)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan2->id,
        'started_at' => $week3Start->copy()->addDays(2),
        'finished_at' => $week3Start->copy()->addDays(2)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan3->id,
        'started_at' => $week3Start->copy()->addDays(3),
        'finished_at' => $week3Start->copy()->addDays(3)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan4->id,
        'started_at' => $week3Start->copy()->addDays(4),
        'finished_at' => $week3Start->copy()->addDays(4)->addMinutes(60),
    ]);

    $response = $this->getJson('/api/v1/user/statistics');

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'training_streak_days' => 4, // Последняя неделя: 4 тренировки
            ]
        ]);
});

test('training streak handles multiple cycles correctly', function () {
    // Создаем второй цикл
    $cycle2 = Cycle::factory()->create([
        'user_id' => $this->user->id,
        'start_date' => now()->subWeeks(2),
        'end_date' => null,
    ]);
    
    $plan2_1 = Plan::factory()->create([
        'cycle_id' => $cycle2->id,
        'name' => 'План 2-1',
        'order' => 1,
        'is_active' => true,
    ]);
    
    $plan2_2 = Plan::factory()->create([
        'cycle_id' => $cycle2->id,
        'name' => 'План 2-2',
        'order' => 2,
        'is_active' => true,
    ]);

    // Первый цикл: неделя с полным выполнением (4 тренировки, streak = 4)
    $week1Start = now()->subWeeks(3);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan1->id,
        'started_at' => $week1Start->copy()->addDays(1),
        'finished_at' => $week1Start->copy()->addDays(1)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan2->id,
        'started_at' => $week1Start->copy()->addDays(2),
        'finished_at' => $week1Start->copy()->addDays(2)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan3->id,
        'started_at' => $week1Start->copy()->addDays(3),
        'finished_at' => $week1Start->copy()->addDays(3)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan4->id,
        'started_at' => $week1Start->copy()->addDays(4),
        'finished_at' => $week1Start->copy()->addDays(4)->addMinutes(60),
    ]);

    // Второй цикл: неделя с полным выполнением (2 тренировки, streak = 2)
    $week2Start = now()->subWeeks(1);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $plan2_1->id,
        'started_at' => $week2Start->copy()->addDays(1),
        'finished_at' => $week2Start->copy()->addDays(1)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $plan2_2->id,
        'started_at' => $week2Start->copy()->addDays(2),
        'finished_at' => $week2Start->copy()->addDays(2)->addMinutes(60),
    ]);

    $response = $this->getJson('/api/v1/user/statistics');

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'training_streak_days' => 4, // Максимальный streak из первого цикла (4 > 2)
            ]
        ]);
});

test('training streak returns zero when no workouts', function () {
    $response = $this->getJson('/api/v1/user/statistics');

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'training_streak_days' => 0,
            ]
        ]);
});

test('training streak handles incomplete weeks correctly', function () {
    // Неделя с неполным выполнением (только 2 из 4 планов)
    $weekStart = now()->subWeeks(1);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan1->id,
        'started_at' => $weekStart->copy()->addDays(1),
        'finished_at' => $weekStart->copy()->addDays(1)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan2->id,
        'started_at' => $weekStart->copy()->addDays(2),
        'finished_at' => $weekStart->copy()->addDays(2)->addMinutes(60),
    ]);
    // Планы 3 и 4 не выполнены - это пропуск, streak = 0

    $response = $this->getJson('/api/v1/user/statistics');

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'training_streak_days' => 0, // Пропуск = streak = 0
            ]
        ]);
});

test('training streak continues when more workouts than expected plans', function () {
    // Неделя: выполнено 5 тренировок при 4 планах в цикле (серия продолжается)
    $weekStart = now()->subWeeks(1);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan1->id,
        'started_at' => $weekStart->copy()->addDays(1),
        'finished_at' => $weekStart->copy()->addDays(1)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan2->id,
        'started_at' => $weekStart->copy()->addDays(2),
        'finished_at' => $weekStart->copy()->addDays(2)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan3->id,
        'started_at' => $weekStart->copy()->addDays(3),
        'finished_at' => $weekStart->copy()->addDays(3)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan4->id,
        'started_at' => $weekStart->copy()->addDays(4),
        'finished_at' => $weekStart->copy()->addDays(4)->addMinutes(60),
    ]);
    // Дополнительная тренировка (план 1 повторно)
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan1->id,
        'started_at' => $weekStart->copy()->addDays(5),
        'finished_at' => $weekStart->copy()->addDays(5)->addMinutes(60),
    ]);

    $response = $this->getJson('/api/v1/user/statistics');

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'training_streak_days' => 5, // Все 5 тренировок засчитываются
            ]
        ]);
});

test('training streak increments by one per workout', function () {
    // Неделя 1: 4 тренировки (streak = 4)
    $week1Start = now()->subWeeks(2);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan1->id,
        'started_at' => $week1Start->copy()->addDays(1),
        'finished_at' => $week1Start->copy()->addDays(1)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan2->id,
        'started_at' => $week1Start->copy()->addDays(2),
        'finished_at' => $week1Start->copy()->addDays(2)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan3->id,
        'started_at' => $week1Start->copy()->addDays(3),
        'finished_at' => $week1Start->copy()->addDays(3)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan4->id,
        'started_at' => $week1Start->copy()->addDays(4),
        'finished_at' => $week1Start->copy()->addDays(4)->addMinutes(60),
    ]);

    // Неделя 2: 4 тренировки (streak продолжается: 4 + 4 = 8)
    $week2Start = $week1Start->copy()->addDays(7);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan1->id,
        'started_at' => $week2Start->copy()->addDays(1),
        'finished_at' => $week2Start->copy()->addDays(1)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan2->id,
        'started_at' => $week2Start->copy()->addDays(2),
        'finished_at' => $week2Start->copy()->addDays(2)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan3->id,
        'started_at' => $week2Start->copy()->addDays(3),
        'finished_at' => $week2Start->copy()->addDays(3)->addMinutes(60),
    ]);
    Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan4->id,
        'started_at' => $week2Start->copy()->addDays(4),
        'finished_at' => $week2Start->copy()->addDays(4)->addMinutes(60),
    ]);

    $response = $this->getJson('/api/v1/user/statistics');

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'training_streak_days' => 8, // 4 + 4 = 8 тренировок подряд
            ]
        ]);
});
