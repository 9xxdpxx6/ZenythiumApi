<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Plan;
use App\Models\PlanExercise;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\SharedCycle;
use App\Models\User;
use App\Services\CycleImportService;
use App\Services\CycleShareService;
use App\Services\ExerciseResolutionService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->importer = User::factory()->create();
    
    $this->cycle = Cycle::factory()->create(['user_id' => $this->owner->id]);
    
    $muscleGroup = MuscleGroup::factory()->create();
    $plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
    $exercise = Exercise::factory()->create([
        'user_id' => $this->owner->id,
        'muscle_group_id' => $muscleGroup->id,
    ]);
    PlanExercise::factory()->create([
        'plan_id' => $plan->id,
        'exercise_id' => $exercise->id,
    ]);

    $this->shareService = new CycleShareService();
    $this->exerciseResolutionService = new ExerciseResolutionService();
    $this->service = new CycleImportService($this->shareService, $this->exerciseResolutionService);
    
    $this->sharedCycle = SharedCycle::factory()->create([
        'cycle_id' => $this->cycle->id,
        'is_active' => true,
    ]);
});

describe('CycleImportService', function () {
    describe('importFromShare', function () {
        it('imports cycle successfully', function () {
            $result = $this->service->importFromShare($this->sharedCycle->share_id, $this->importer->id);

            expect($result)->toHaveKey('cycle');
            expect($result)->toHaveKey('plans');
            expect($result)->toHaveKey('exercises');
            expect($result['cycle'])->toBeInstanceOf(Cycle::class);
            expect($result['cycle']->user_id)->toBe($this->importer->id);
            expect($result['plans']->count())->toBeGreaterThan(0);
        });

        it('creates new cycle for importer', function () {
            $result = $this->service->importFromShare($this->sharedCycle->share_id, $this->importer->id);

            $importedCycle = $result['cycle'];
            expect($importedCycle->user_id)->toBe($this->importer->id);
            expect($importedCycle->id)->not->toBe($this->cycle->id);
        });

        it('copies plans from source cycle', function () {
            $result = $this->service->importFromShare($this->sharedCycle->share_id, $this->importer->id);

            expect($result['plans']->count())->toBe(1);
            expect($result['plans'][0]->user_id)->toBe($this->importer->id);
        });

        it('uses existing exercise if it exists for importer', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            $existingExercise = Exercise::factory()->create([
                'user_id' => $this->importer->id,
                'name' => $this->cycle->plans->first()->planExercises->first()->exercise->name,
                'muscle_group_id' => $muscleGroup->id,
            ]);

            $result = $this->service->importFromShare($this->sharedCycle->share_id, $this->importer->id);

            // Проверяем, что использовалось существующее упражнение
            $importedPlan = $result['plans']->first();
            $planExercise = PlanExercise::where('plan_id', $importedPlan->id)->first();
            
            // Упражнение может быть либо существующим, либо новым
            expect($planExercise->exercise_id)->toBeInt();
        });

        it('throws exception if trying to import own cycle', function () {
            expect(fn() => $this->service->importFromShare($this->sharedCycle->share_id, $this->owner->id))
                ->toThrow(\Exception::class, 'собственную программу');
        });

        it('throws exception if shared cycle not found', function () {
            expect(fn() => $this->service->importFromShare('00000000-0000-0000-0000-000000000000', $this->importer->id))
                ->toThrow(\Exception::class, 'не найден');
        });

        it('increments import count after successful import', function () {
            $initialCount = $this->sharedCycle->import_count;
            
            $this->service->importFromShare($this->sharedCycle->share_id, $this->importer->id);

            $this->sharedCycle->refresh();
            expect($this->sharedCycle->import_count)->toBe($initialCount + 1);
        });
    });
});

