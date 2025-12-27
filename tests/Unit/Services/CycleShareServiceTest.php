<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Plan;
use App\Models\PlanExercise;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\SharedCycle;
use App\Models\User;
use App\Services\CycleShareService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    $this->service = new CycleShareService();
});

describe('CycleShareService', function () {
    describe('generateShareLink', function () {
        it('generates share link for cycle', function () {
            $link = $this->service->generateShareLink($this->cycle->id, $this->user->id);

            expect($link)->toBeString();
            expect($link)->toContain('/shared-cycles/');
            
            $sharedCycle = SharedCycle::where('cycle_id', $this->cycle->id)->first();
            expect($sharedCycle)->not->toBeNull();
            expect($sharedCycle->share_id)->toBeString();
        });

        it('returns existing link if already shared', function () {
            $link1 = $this->service->generateShareLink($this->cycle->id, $this->user->id);
            $link2 = $this->service->generateShareLink($this->cycle->id, $this->user->id);

            expect($link1)->toBe($link2);
            expect(SharedCycle::where('cycle_id', $this->cycle->id)->count())->toBe(1);
        });

        it('throws exception if cycle not found', function () {
            expect(fn() => $this->service->generateShareLink(99999, $this->user->id))
                ->toThrow(\Exception::class, 'не найден');
        });

        it('throws exception if user is not owner', function () {
            $otherUser = User::factory()->create();
            
            expect(fn() => $this->service->generateShareLink($this->cycle->id, $otherUser->id))
                ->toThrow(\Exception::class);
        });
    });

    describe('getSharedCycle', function () {
        it('returns shared cycle by share_id', function () {
            $sharedCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'is_active' => true,
            ]);

            $result = $this->service->getSharedCycle($sharedCycle->share_id);

            expect($result)->toBeInstanceOf(SharedCycle::class);
            expect($result->id)->toBe($sharedCycle->id);
        });

        it('returns null if share_id not found', function () {
            $result = $this->service->getSharedCycle('00000000-0000-0000-0000-000000000000');
            
            expect($result)->toBeNull();
        });

        it('returns null if shared cycle is inactive', function () {
            $sharedCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'is_active' => false,
            ]);

            $result = $this->service->getSharedCycle($sharedCycle->share_id);
            
            expect($result)->toBeNull();
        });

        it('returns null if shared cycle is expired', function () {
            $sharedCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'is_active' => true,
                'expires_at' => now()->subDay(),
            ]);

            $result = $this->service->getSharedCycle($sharedCycle->share_id);
            
            expect($result)->toBeNull();
        });
    });

    describe('getSharedCycleData', function () {
        it('returns cycle data structure', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            $plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            $exercise = Exercise::factory()->create([
                'user_id' => $this->cycle->user_id,
                'muscle_group_id' => $muscleGroup->id,
            ]);
            PlanExercise::factory()->create([
                'plan_id' => $plan->id,
                'exercise_id' => $exercise->id,
            ]);

            $sharedCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'is_active' => true,
            ]);

            $data = $this->service->getSharedCycleData($sharedCycle->share_id);

            expect($data)->toBeArray();
            expect($data)->toHaveKey('cycles');
            expect($data['cycles'][0])->toHaveKey('name');
            expect($data['cycles'][0])->toHaveKey('plans');
        });

        it('caches shared cycle data', function () {
            $sharedCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'is_active' => true,
            ]);

            Cache::flush();
            
            $data1 = $this->service->getSharedCycleData($sharedCycle->share_id);
            $cacheKey = "shared_cycle_data_{$sharedCycle->share_id}";
            
            expect(Cache::has($cacheKey))->toBeTrue();
            
            $data2 = $this->service->getSharedCycleData($sharedCycle->share_id);
            
            expect($data1)->toBe($data2);
        });
    });

    describe('incrementViewCount', function () {
        it('increments view count', function () {
            $sharedCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'view_count' => 5,
            ]);

            $this->service->incrementViewCount($sharedCycle->share_id);

            $sharedCycle->refresh();
            expect($sharedCycle->view_count)->toBe(6);
        });
    });

    describe('incrementImportCount', function () {
        it('increments import count', function () {
            $sharedCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'import_count' => 3,
            ]);

            $this->service->incrementImportCount($sharedCycle->share_id);

            $sharedCycle->refresh();
            expect($sharedCycle->import_count)->toBe(4);
        });
    });
});

