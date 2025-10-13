<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Plan;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    
    // Создаем временный цикл для планов (так как cycle_id не может быть null)
    $tempCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    
    // Создаем несколько планов для тестирования
    $this->plan1 = Plan::factory()->create([
        'cycle_id' => $tempCycle->id,
        'name' => 'План 1',
        'order' => 1
    ]);
    $this->plan2 = Plan::factory()->create([
        'cycle_id' => $tempCycle->id,
        'name' => 'План 2', 
        'order' => 2
    ]);
    $this->plan3 = Plan::factory()->create([
        'cycle_id' => $tempCycle->id,
        'name' => 'План 3',
        'order' => 3
    ]);
});

describe('CycleController with plan_ids', function () {
    describe('POST /api/cycles', function () {
        it('creates cycle with plan_ids', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/cycles', [
                    'name' => 'Новый цикл с планами',
                    'weeks' => 4,
                    'plan_ids' => [$this->plan1->id, $this->plan2->id, $this->plan3->id]
                ]);

            $response->assertStatus(201);
            
            // Проверяем, что цикл создан
            $cycle = Cycle::where('name', 'Новый цикл с планами')->first();
            expect($cycle)->not->toBeNull();
            
            // Проверяем, что планы привязаны к циклу
            $this->plan1->refresh();
            $this->plan2->refresh();
            $this->plan3->refresh();
            
            expect($this->plan1->cycle_id)->toBe($cycle->id);
            expect($this->plan2->cycle_id)->toBe($cycle->id);
            expect($this->plan3->cycle_id)->toBe($cycle->id);
            
            // Проверяем порядок
            expect($this->plan1->order)->toBe(1);
            expect($this->plan2->order)->toBe(2);
            expect($this->plan3->order)->toBe(3);
        });

        it('creates cycle without plan_ids', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/cycles', [
                    'name' => 'Цикл без планов',
                    'weeks' => 4
                ]);

            $response->assertStatus(201);
            
            $cycle = Cycle::where('name', 'Цикл без планов')->first();
            expect($cycle)->not->toBeNull();
        });

        it('validates plan_ids array', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/cycles', [
                    'name' => 'Цикл с невалидными планами',
                    'weeks' => 4,
                    'plan_ids' => 'not_an_array'
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['plan_ids']);
        });

        it('validates plan_ids exist', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/cycles', [
                    'name' => 'Цикл с несуществующими планами',
                    'weeks' => 4,
                    'plan_ids' => [999999, 999998]
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['plan_ids.0', 'plan_ids.1']);
        });

        it('validates plan_ids max count', function () {
            $tempCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            $planIds = [];
            for ($i = 0; $i < 25; $i++) {
                $plan = Plan::factory()->create(['cycle_id' => $tempCycle->id]);
                $planIds[] = $plan->id;
            }

            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/cycles', [
                    'name' => 'Цикл со слишком большим количеством планов',
                    'weeks' => 4,
                    'plan_ids' => $planIds
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['plan_ids']);
        });
    });

    describe('PUT /api/cycles/{id}', function () {
        it('updates cycle with new plan_ids', function () {
            // Сначала создаем цикл с планами
            $cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            $this->plan1->update(['cycle_id' => $cycle->id, 'order' => 1]);
            $this->plan2->update(['cycle_id' => $cycle->id, 'order' => 2]);

            // Обновляем цикл с новыми планами
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/cycles/{$cycle->id}", [
                    'name' => 'Обновленный цикл',
                    'weeks' => 6,
                    'plan_ids' => [$this->plan2->id, $this->plan3->id]
                ]);

            $response->assertStatus(200);

            // Проверяем, что старые планы отвязаны (перемещены в другой цикл)
            $this->plan1->refresh();
            expect($this->plan1->cycle_id)->not->toBe($cycle->id);
            expect($this->plan1->cycle_id)->not->toBeNull();

            // Проверяем, что новые планы привязаны
            $this->plan2->refresh();
            $this->plan3->refresh();
            expect($this->plan2->cycle_id)->toBe($cycle->id);
            expect($this->plan3->cycle_id)->toBe($cycle->id);

            // Проверяем порядок
            expect($this->plan2->order)->toBe(1);
            expect($this->plan3->order)->toBe(2);
        });

        it('updates cycle to remove all plans', function () {
            $cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            $this->plan1->update(['cycle_id' => $cycle->id]);

            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/cycles/{$cycle->id}", [
                    'name' => 'Цикл без планов',
                    'weeks' => 4,
                    'plan_ids' => []
                ]);

            $response->assertStatus(200);

            // План должен быть перемещен в другой цикл (не null)
            $this->plan1->refresh();
            expect($this->plan1->cycle_id)->not->toBe($cycle->id);
            expect($this->plan1->cycle_id)->not->toBeNull();
        });

        it('updates cycle without changing plan_ids when not provided', function () {
            $cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            $this->plan1->update(['cycle_id' => $cycle->id]);

            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/cycles/{$cycle->id}", [
                    'name' => 'Обновленный цикл',
                    'weeks' => 6
                    // plan_ids не передаем
                ]);

            $response->assertStatus(200);

            // План должен остаться привязанным
            $this->plan1->refresh();
            expect($this->plan1->cycle_id)->toBe($cycle->id);
        });
    });
});
