<?php

declare(strict_types=1);

use App\Models\MuscleGroup;

describe('MuscleGroupController Authentication', function () {
    it('requires authentication for all endpoints', function () {
        $muscleGroup = MuscleGroup::factory()->create();

        // Test without authentication
        $this->getJson('/api/muscle-groups')->assertStatus(401);
        $this->postJson('/api/muscle-groups', ['name' => 'Test'])->assertStatus(401);
        $this->getJson("/api/muscle-groups/{$muscleGroup->id}")->assertStatus(401);
        $this->putJson("/api/muscle-groups/{$muscleGroup->id}", ['name' => 'Test'])->assertStatus(401);
        $this->deleteJson("/api/muscle-groups/{$muscleGroup->id}")->assertStatus(401);
    });
});
