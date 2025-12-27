<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\SharedCycle;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
});

describe('SharedCycle', function () {
    describe('relationships', function () {
        it('belongs to cycle', function () {
            $sharedCycle = SharedCycle::factory()->create(['cycle_id' => $this->cycle->id]);

            expect($sharedCycle->cycle)->toBeInstanceOf(Cycle::class);
            expect($sharedCycle->cycle->id)->toBe($this->cycle->id);
        });
    });

    describe('isExpired', function () {
        it('returns false if expires_at is null', function () {
            $sharedCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'expires_at' => null,
            ]);

            expect($sharedCycle->isExpired())->toBeFalse();
        });

        it('returns false if expires_at is in future', function () {
            $sharedCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'expires_at' => now()->addDay(),
            ]);

            expect($sharedCycle->isExpired())->toBeFalse();
        });

        it('returns true if expires_at is in past', function () {
            $sharedCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'expires_at' => now()->subDay(),
            ]);

            expect($sharedCycle->isExpired())->toBeTrue();
        });
    });

    describe('isAccessible', function () {
        it('returns true if active and not expired', function () {
            $sharedCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'is_active' => true,
                'expires_at' => null,
            ]);

            expect($sharedCycle->isAccessible())->toBeTrue();
        });

        it('returns false if inactive', function () {
            $sharedCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'is_active' => false,
                'expires_at' => null,
            ]);

            expect($sharedCycle->isAccessible())->toBeFalse();
        });

        it('returns false if expired', function () {
            $sharedCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'is_active' => true,
                'expires_at' => now()->subDay(),
            ]);

            expect($sharedCycle->isAccessible())->toBeFalse();
        });
    });

    describe('incrementViewCount', function () {
        it('increments view count', function () {
            $sharedCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'view_count' => 5,
            ]);

            $sharedCycle->incrementViewCount();
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

            $sharedCycle->incrementImportCount();
            $sharedCycle->refresh();

            expect($sharedCycle->import_count)->toBe(4);
        });
    });
});

