<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Plan;
use App\Models\PlanExercise;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    
    // Создаем упражнения для тестирования
    $muscleGroup = MuscleGroup::factory()->create();
    $this->exercise1 = Exercise::factory()->create(['user_id' => $this->user->id, 'muscle_group_id' => $muscleGroup->id]);
    $this->exercise2 = Exercise::factory()->create(['user_id' => $this->user->id, 'muscle_group_id' => $muscleGroup->id]);
    $this->exercise3 = Exercise::factory()->create(['user_id' => $this->user->id, 'muscle_group_id' => $muscleGroup->id]);
});

describe('PlanController with exercise_ids', function () {
    describe('POST /api/plans', function () {
        it('creates plan with exercise_ids', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/plans', [
                    'cycle_id' => $this->cycle->id,
                    'name' => 'Новый план с упражнениями',
                    'order' => 1,
                    'exercise_ids' => [$this->exercise1->id, $this->exercise2->id, $this->exercise3->id]
                ]);

            $response->assertStatus(201);
            
            // Проверяем, что план создан
            $plan = Plan::where('name', 'Новый план с упражнениями')->first();
            expect($plan)->not->toBeNull();
            
            // Проверяем, что упражнения привязаны к плану
            $planExercises = PlanExercise::where('plan_id', $plan->id)->orderBy('order')->get();
            expect($planExercises)->toHaveCount(3);
            expect($planExercises->pluck('exercise_id')->toArray())->toBe([$this->exercise1->id, $this->exercise2->id, $this->exercise3->id]);
            expect($planExercises->pluck('order')->toArray())->toBe([1, 2, 3]);
        });

        it('creates plan without exercise_ids', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/plans', [
                    'cycle_id' => $this->cycle->id,
                    'name' => 'План без упражнений',
                    'order' => 1
                ]);

            $response->assertStatus(201);
            
            $plan = Plan::where('name', 'План без упражнений')->first();
            expect($plan)->not->toBeNull();
            
            // Проверяем, что упражнения не добавлены
            $planExercises = PlanExercise::where('plan_id', $plan->id)->get();
            expect($planExercises)->toHaveCount(0);
        });

        it('validates exercise_ids array', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/plans', [
                    'cycle_id' => $this->cycle->id,
                    'name' => 'План с невалидными упражнениями',
                    'order' => 1,
                    'exercise_ids' => 'not_an_array'
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['exercise_ids']);
        });

        it('validates exercise_ids exist', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/plans', [
                    'cycle_id' => $this->cycle->id,
                    'name' => 'План с несуществующими упражнениями',
                    'order' => 1,
                    'exercise_ids' => [999999, 999998]
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['exercise_ids.0', 'exercise_ids.1']);
        });

        it('validates exercise_ids integer elements', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/plans', [
                    'cycle_id' => $this->cycle->id,
                    'name' => 'План с невалидными элементами упражнений',
                    'order' => 1,
                    'exercise_ids' => ['not-a-number', 'also-not-a-number']
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['exercise_ids.0', 'exercise_ids.1']);
        });
    });

    describe('PUT /api/plans/{id}', function () {
        it('updates plan with new exercise_ids', function () {
            // Сначала создаем план с упражнениями
            $plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            PlanExercise::create(['plan_id' => $plan->id, 'exercise_id' => $this->exercise1->id, 'order' => 1]);
            PlanExercise::create(['plan_id' => $plan->id, 'exercise_id' => $this->exercise2->id, 'order' => 2]);

            // Обновляем план с новыми упражнениями
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/plans/{$plan->id}", [
                    'cycle_id' => $this->cycle->id,
                    'name' => 'Обновленный план',
                    'order' => 1,
                    'exercise_ids' => [$this->exercise2->id, $this->exercise3->id]
                ]);

            $response->assertStatus(200);

            // Проверяем, что старые упражнения удалены, а новые добавлены
            $planExercises = PlanExercise::where('plan_id', $plan->id)->orderBy('order')->get();
            expect($planExercises)->toHaveCount(2);
            expect($planExercises->pluck('exercise_id')->toArray())->toBe([$this->exercise2->id, $this->exercise3->id]);
            expect($planExercises->pluck('order')->toArray())->toBe([1, 2]);
        });

        it('updates plan to remove all exercises', function () {
            $plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            PlanExercise::create(['plan_id' => $plan->id, 'exercise_id' => $this->exercise1->id, 'order' => 1]);
            PlanExercise::create(['plan_id' => $plan->id, 'exercise_id' => $this->exercise2->id, 'order' => 2]);

            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/plans/{$plan->id}", [
                    'cycle_id' => $this->cycle->id,
                    'name' => 'План без упражнений',
                    'order' => 1,
                    'exercise_ids' => []
                ]);

            $response->assertStatus(200);

            // Проверяем, что все упражнения удалены
            $planExercises = PlanExercise::where('plan_id', $plan->id)->get();
            expect($planExercises)->toHaveCount(0);
        });

        it('updates plan without changing exercise_ids when not provided', function () {
            $plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            PlanExercise::create(['plan_id' => $plan->id, 'exercise_id' => $this->exercise1->id, 'order' => 1]);
            PlanExercise::create(['plan_id' => $plan->id, 'exercise_id' => $this->exercise2->id, 'order' => 2]);

            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/plans/{$plan->id}", [
                    'cycle_id' => $this->cycle->id,
                    'name' => 'Обновленный план',
                    'order' => 1
                    // exercise_ids не передаем
                ]);

            $response->assertStatus(200);

            // Проверяем, что упражнения остались без изменений
            $planExercises = PlanExercise::where('plan_id', $plan->id)->orderBy('order')->get();
            expect($planExercises)->toHaveCount(2);
            expect($planExercises->pluck('exercise_id')->toArray())->toBe([$this->exercise1->id, $this->exercise2->id]);
        });

        it('validates exercise_ids on update', function () {
            $plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);

            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/plans/{$plan->id}", [
                    'cycle_id' => $this->cycle->id,
                    'name' => 'План с невалидными упражнениями',
                    'order' => 1,
                    'exercise_ids' => 'not_an_array'
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['exercise_ids']);
        });
    });

    describe('GET /api/plans/{id}', function () {
        it('returns plan with exercises in correct order', function () {
            $plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            PlanExercise::create(['plan_id' => $plan->id, 'exercise_id' => $this->exercise1->id, 'order' => 2]);
            PlanExercise::create(['plan_id' => $plan->id, 'exercise_id' => $this->exercise2->id, 'order' => 1]);
            PlanExercise::create(['plan_id' => $plan->id, 'exercise_id' => $this->exercise3->id, 'order' => 3]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/plans/{$plan->id}");

            $response->assertStatus(200);
            
            $responseData = $response->json('data');
            expect($responseData['exercises'])->toHaveCount(3);
            
            // Проверяем порядок упражнений
            $exerciseOrders = collect($responseData['exercises'])->pluck('order')->toArray();
            expect($exerciseOrders)->toBe([1, 2, 3]);
            
            $exerciseIds = collect($responseData['exercises'])->pluck('id')->toArray();
            expect($exerciseIds)->toBe([$this->exercise2->id, $this->exercise1->id, $this->exercise3->id]);
        });
    });
});
