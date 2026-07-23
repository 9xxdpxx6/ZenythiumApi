<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('EnsureIdempotentRequest middleware', function () {
    it('returns the same response and does not create a duplicate on a retried request with the same key', function () {
        $payload = ['name' => 'Push Pull Legs', 'weeks' => 8];

        $first = $this->actingAs($this->user)
            ->postJson('/api/v1/cycles', $payload, ['Idempotency-Key' => 'retry-key-1']);
        $first->assertStatus(201);

        $retry = $this->actingAs($this->user)
            ->postJson('/api/v1/cycles', $payload, ['Idempotency-Key' => 'retry-key-1']);
        $retry->assertStatus(201);

        expect($retry->json('data.id'))->toBe($first->json('data.id'));
        expect(Cycle::where('name', 'Push Pull Legs')->count())->toBe(1);
    });

    it('does not dedupe requests that omit the Idempotency-Key header', function () {
        $payload = ['name' => 'No Header Cycle', 'weeks' => 8];

        $first = $this->actingAs($this->user)->postJson('/api/v1/cycles', $payload);
        $first->assertStatus(201);

        // Without the header, this is a genuinely new request against the same
        // (already-taken) unique name — proves no dedup/caching happened,
        // the controller actually re-ran and hit normal validation.
        $second = $this->actingAs($this->user)->postJson('/api/v1/cycles', $payload);
        $second->assertStatus(422);
    });

    it('scopes the idempotency cache per user, so two users with the same key get separate resources', function () {
        $otherUser = User::factory()->create();
        $payload = ['name' => 'Shared Key Cycle', 'weeks' => 8];

        $first = $this->actingAs($this->user)
            ->postJson('/api/v1/cycles', $payload, ['Idempotency-Key' => 'same-key']);
        $first->assertStatus(201);

        $second = $this->actingAs($otherUser)
            ->postJson('/api/v1/cycles', $payload, ['Idempotency-Key' => 'same-key']);
        $second->assertStatus(201);

        expect($second->json('data.id'))->not->toBe($first->json('data.id'));
        expect(Cycle::where('name', 'Shared Key Cycle')->count())->toBe(2);
    });

    it('does not cache a failed response, so a corrected retry with the same key still succeeds', function () {
        $failing = $this->actingAs($this->user)
            ->postJson('/api/v1/cycles', ['name' => 'Fixable Cycle'], ['Idempotency-Key' => 'fix-me']);
        $failing->assertStatus(422);

        $fixed = $this->actingAs($this->user)
            ->postJson('/api/v1/cycles', ['name' => 'Fixable Cycle', 'weeks' => 8], ['Idempotency-Key' => 'fix-me']);
        $fixed->assertStatus(201);

        expect(Cycle::where('name', 'Fixable Cycle')->count())->toBe(1);
    });
});
