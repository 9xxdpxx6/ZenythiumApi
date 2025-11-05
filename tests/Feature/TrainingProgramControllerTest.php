<?php

declare(strict_types=1);

use App\Models\TrainingProgram;
use App\Models\TrainingProgramInstallation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->program = TrainingProgram::factory()->create([
        'name' => 'Beginner',
        'is_active' => true,
    ]);
});

describe('TrainingProgramController', function () {
    describe('GET /api/v1/training-programs', function () {
        it('returns list of training programs', function () {
            TrainingProgram::factory()->count(3)->create(['is_active' => true]);
            
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/training-programs');
            
            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'author',
                            'duration_weeks',
                            'is_active',
                            'is_installed',
                            'installations_count',
                            'cycles_count',
                            'plans_count',
                            'exercises_count',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'message',
                    'meta',
                ]);
        });

        it('returns installations_count for programs', function () {
            $program1 = TrainingProgram::factory()->create();
            $program2 = TrainingProgram::factory()->create();
            
            // Создаем установки для program1
            TrainingProgramInstallation::factory()->count(3)->create([
                'training_program_id' => $program1->id,
            ]);
            
            // Создаем установки для program2
            TrainingProgramInstallation::factory()->count(2)->create([
                'training_program_id' => $program2->id,
            ]);
            
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/training-programs');
            
            $response->assertStatus(200);
            
            $program1Data = collect($response->json('data'))
                ->firstWhere('id', $program1->id);
            $program2Data = collect($response->json('data'))
                ->firstWhere('id', $program2->id);
            
            expect($program1Data['installations_count'])->toBe(3);
            expect($program2Data['installations_count'])->toBe(2);
        });

        it('returns is_installed status for current user', function () {
            $otherUser = User::factory()->create();
            
            // Устанавливаем программу для другого пользователя
            TrainingProgramInstallation::factory()->create([
                'training_program_id' => $this->program->id,
                'user_id' => $otherUser->id,
            ]);
            
            // Проверяем, что текущий пользователь не установил программу
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/training-programs');
            
            $response->assertStatus(200);
            
            $programData = collect($response->json('data'))
                ->firstWhere('id', $this->program->id);
            
            expect($programData['is_installed'])->toBeFalse();
            
            // Устанавливаем программу для текущего пользователя
            TrainingProgramInstallation::factory()->create([
                'training_program_id' => $this->program->id,
                'user_id' => $this->user->id,
            ]);
            
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/training-programs');
            
            $response->assertStatus(200);
            
            $programData = collect($response->json('data'))
                ->firstWhere('id', $this->program->id);
            
            expect($programData['is_installed'])->toBeTrue();
        });

        it('returns cycles_count, plans_count, exercises_count', function () {
            // Очищаем кэш перед тестом
            Cache::flush();
            
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/training-programs');
            
            $response->assertStatus(200);
            
            $programData = collect($response->json('data'))
                ->firstWhere('id', $this->program->id);
            
            // Проверяем, что поля присутствуют и являются числами
            expect($programData)->toHaveKey('cycles_count');
            expect($programData)->toHaveKey('plans_count');
            expect($programData)->toHaveKey('exercises_count');
            expect($programData['cycles_count'])->toBeInt();
            expect($programData['plans_count'])->toBeInt();
            expect($programData['exercises_count'])->toBeInt();
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/training-programs');
            
            $response->assertStatus(401);
        });

        it('can filter by is_active', function () {
            TrainingProgram::factory()->active()->count(2)->create();
            TrainingProgram::factory()->inactive()->count(3)->create();
            
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/training-programs?is_active=1');
            
            $response->assertStatus(200);
            
            $programs = $response->json('data');
            expect($programs)->toBeArray();
            
            // Все программы должны быть активными
            foreach ($programs as $program) {
                expect($program['is_active'])->toBeTrue();
            }
        });

        it('can search by name', function () {
            TrainingProgram::factory()->create(['name' => 'Beginner Program']);
            TrainingProgram::factory()->create(['name' => 'Advanced Program']);
            
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/training-programs?search=Beginner');
            
            $response->assertStatus(200);
            
            $programs = $response->json('data');
            expect($programs)->toBeArray();
            
            // Должна быть найдена программа с "Beginner" в названии
            $found = false;
            foreach ($programs as $program) {
                if (str_contains($program['name'], 'Beginner')) {
                    $found = true;
                    break;
                }
            }
            expect($found)->toBeTrue();
        });
    });

    describe('GET /api/v1/training-programs/{id}', function () {
        it('returns single training program', function () {
            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/training-programs/{$this->program->id}");
            
            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'description',
                        'author',
                        'duration_weeks',
                        'is_active',
                        'is_installed',
                        'installations_count',
                        'structure',
                        'created_at',
                        'updated_at',
                    ],
                    'message',
                ]);
        });

        it('returns installations_count for single program', function () {
            TrainingProgramInstallation::factory()->count(5)->create([
                'training_program_id' => $this->program->id,
            ]);
            
            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/training-programs/{$this->program->id}");
            
            $response->assertStatus(200);
            expect($response->json('data.installations_count'))->toBe(5);
        });

        it('returns is_installed status for current user', function () {
            // Программа не установлена
            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/training-programs/{$this->program->id}");
            
            $response->assertStatus(200);
            expect($response->json('data.is_installed'))->toBeFalse();
            
            // Устанавливаем программу
            TrainingProgramInstallation::factory()->create([
                'training_program_id' => $this->program->id,
                'user_id' => $this->user->id,
            ]);
            
            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/training-programs/{$this->program->id}");
            
            $response->assertStatus(200);
            expect($response->json('data.is_installed'))->toBeTrue();
        });

        it('returns 404 for non-existent program', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/training-programs/99999');
            
            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->getJson("/api/v1/training-programs/{$this->program->id}");
            
            $response->assertStatus(401);
        });
    });

    describe('Caching', function () {
        it('caches program structure data', function () {
            Cache::flush();
            
            // Первый запрос - кэш пуст
            $response1 = $this->actingAs($this->user)
                ->getJson("/api/v1/training-programs/{$this->program->id}");
            
            $response1->assertStatus(200);
            
            // Проверяем, что данные закэшированы
            $cacheKey = "training_program_data_{$this->program->id}_{$this->program->name}";
            expect(Cache::has($cacheKey))->toBeTrue();
            
            // Второй запрос - должен использовать кэш
            $response2 = $this->actingAs($this->user)
                ->getJson("/api/v1/training-programs/{$this->program->id}");
            
            $response2->assertStatus(200);
            expect($response2->json('data.id'))->toBe($response1->json('data.id'));
        });

        it('caches program counts', function () {
            Cache::flush();
            
            // Первый запрос - кэш пуст
            $response1 = $this->actingAs($this->user)
                ->getJson('/api/v1/training-programs');
            
            $response1->assertStatus(200);
            
            // Проверяем, что counts закэшированы
            $cacheKey = "training_program_counts_{$this->program->id}";
            expect(Cache::has($cacheKey))->toBeTrue();
            
            // Второй запрос - должен использовать кэш
            $response2 = $this->actingAs($this->user)
                ->getJson('/api/v1/training-programs');
            
            $response2->assertStatus(200);
            
            $program1Data = collect($response1->json('data'))
                ->firstWhere('id', $this->program->id);
            $program2Data = collect($response2->json('data'))
                ->firstWhere('id', $this->program->id);
            
            expect($program1Data['cycles_count'])->toBe($program2Data['cycles_count']);
            expect($program1Data['plans_count'])->toBe($program2Data['plans_count']);
            expect($program1Data['exercises_count'])->toBe($program2Data['exercises_count']);
        });
    });
});

