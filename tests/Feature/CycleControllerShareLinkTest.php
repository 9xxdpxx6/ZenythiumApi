<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\SharedCycle;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
});

describe('CycleController shareLink', function () {
    describe('GET /api/v1/cycles/{id}/share-link', function () {
        it('generates share link successfully', function () {
            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/cycles/{$this->cycle->id}/share-link");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'share_link',
                    'share_id',
                    'message',
                ]);

            expect($response->json('share_link'))->toBeString();
            expect($response->json('share_id'))->toBeString();
        });

        it('returns existing link if already shared', function () {
            $response1 = $this->actingAs($this->user)
                ->getJson("/api/v1/cycles/{$this->cycle->id}/share-link");
            
            $shareId1 = $response1->json('share_id');

            $response2 = $this->actingAs($this->user)
                ->getJson("/api/v1/cycles/{$this->cycle->id}/share-link");

            expect($response2->json('share_id'))->toBe($shareId1);
            expect(SharedCycle::where('cycle_id', $this->cycle->id)->count())->toBe(1);
        });

        it('returns 401 for unauthenticated user', function () {
            $response = $this->getJson("/api/v1/cycles/{$this->cycle->id}/share-link");

            $response->assertStatus(401);
        });

        it('returns 403 when user is not owner', function () {
            $response = $this->actingAs($this->otherUser)
                ->getJson("/api/v1/cycles/{$this->cycle->id}/share-link");

            $response->assertStatus(403);
        });

        it('returns 404 for non-existent cycle', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/cycles/99999/share-link');

            $response->assertStatus(404);
        });

        it('creates shared_cycle record', function () {
            $this->actingAs($this->user)
                ->getJson("/api/v1/cycles/{$this->cycle->id}/share-link");

            $sharedCycle = SharedCycle::where('cycle_id', $this->cycle->id)->first();
            expect($sharedCycle)->not->toBeNull();
            expect($sharedCycle->is_active)->toBeTrue();
        });
    });
});

