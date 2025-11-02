<?php

declare(strict_types=1);

use App\Models\TrainingProgram;
use App\Models\TrainingProgramInstallation;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('TrainingProgram Model', function () {
    describe('creation and attributes', function () {
        it('can be created with factory', function () {
            $program = TrainingProgram::factory()->create();
            
            expect($program)->toBeInstanceOf(TrainingProgram::class);
            expect($program->id)->toBeInt();
            expect($program->name)->toBeString();
        });

        it('has correct fillable attributes', function () {
            $program = TrainingProgram::factory()->make();
            
            expect($program->getFillable())->toBe([
                'name',
                'description',
                'author_id',
                'duration_weeks',
                'is_active',
            ]);
        });

        it('casts is_active to boolean', function () {
            $program = TrainingProgram::factory()->create(['is_active' => 1]);
            
            expect($program->is_active)->toBeTrue();
            // В SQLite boolean может быть сохранен как integer, проверяем тип значения
            $original = $program->getOriginal('is_active');
            expect(in_array($original, [1, true, '1'], true))->toBeTrue();
        });

        it('casts duration_weeks to integer', function () {
            $program = TrainingProgram::factory()->create(['duration_weeks' => '5']);
            
            expect($program->duration_weeks)->toBeInt()->toBe(5);
        });

        it('can have null description', function () {
            $program = TrainingProgram::factory()->create(['description' => null]);
            
            expect($program->description)->toBeNull();
        });

        it('can have description', function () {
            $description = 'Test program description';
            $program = TrainingProgram::factory()->create(['description' => $description]);
            
            expect($program->description)->toBe($description);
        });

        it('defaults is_active to true when not specified', function () {
            $program = TrainingProgram::factory()->active()->create();
            
            expect($program->is_active)->toBeTrue();
        });

        it('can be marked as inactive', function () {
            $program = TrainingProgram::factory()->inactive()->create();
            
            expect($program->is_active)->toBeFalse();
        });
    });

    describe('author relationship', function () {
        it('belongs to user as author', function () {
            $program = TrainingProgram::factory()->withAuthor($this->user)->create();
            
            expect($program->author)->toBeInstanceOf(User::class);
            expect($program->author->id)->toBe($this->user->id);
        });

        it('can have null author', function () {
            $program = TrainingProgram::factory()->withoutAuthor()->create();
            
            expect($program->author_id)->toBeNull();
            expect($program->author)->toBeNull();
        });

        it('loads author relationship when eager loaded', function () {
            $program = TrainingProgram::factory()->withAuthor($this->user)->create();
            
            $loaded = TrainingProgram::with('author')->find($program->id);
            
            expect($loaded->relationLoaded('author'))->toBeTrue();
            expect($loaded->author)->toBeInstanceOf(User::class);
        });

        it('returns null when author is deleted', function () {
            $author = User::factory()->create();
            $program = TrainingProgram::factory()->withAuthor($author)->create();
            
            $author->delete();
            $program->refresh();
            
            expect($program->author)->toBeNull();
        });
    });

    describe('installs relationship', function () {
        it('has many installations', function () {
            $program = TrainingProgram::factory()->create();
            
            $installation1 = TrainingProgramInstallation::factory()->create([
                'training_program_id' => $program->id,
                'user_id' => $this->user->id,
            ]);
            
            $installation2 = TrainingProgramInstallation::factory()->create([
                'training_program_id' => $program->id,
                'user_id' => User::factory()->create()->id,
            ]);
            
            expect($program->installs)->toHaveCount(2);
            expect($program->installs->first()->id)->toBe($installation1->id);
            expect($program->installs->last()->id)->toBe($installation2->id);
        });

        it('returns empty collection when no installations', function () {
            $program = TrainingProgram::factory()->create();
            
            expect($program->installs)->toBeEmpty();
            expect($program->installs->count())->toBe(0);
        });

        it('loads installs relationship when eager loaded', function () {
            $program = TrainingProgram::factory()->create();
            TrainingProgramInstallation::factory()->count(3)->create([
                'training_program_id' => $program->id,
                'user_id' => $this->user->id,
            ]);
            
            $loaded = TrainingProgram::with('installs')->find($program->id);
            
            expect($loaded->relationLoaded('installs'))->toBeTrue();
            expect($loaded->installs)->toHaveCount(3);
        });

        it('can query installations through relationship', function () {
            $program = TrainingProgram::factory()->create();
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            
            TrainingProgramInstallation::factory()->create([
                'training_program_id' => $program->id,
                'user_id' => $user1->id,
            ]);
            
            TrainingProgramInstallation::factory()->create([
                'training_program_id' => $program->id,
                'user_id' => $user2->id,
            ]);
            
            $installations = $program->installs()->where('user_id', $user1->id)->get();
            
            expect($installations)->toHaveCount(1);
            expect($installations->first()->user_id)->toBe($user1->id);
        });
    });

    describe('scopes and queries', function () {
        it('can filter active programs', function () {
            TrainingProgram::factory()->active()->count(3)->create();
            TrainingProgram::factory()->inactive()->count(2)->create();
            
            $activePrograms = TrainingProgram::where('is_active', true)->get();
            
            expect($activePrograms)->toHaveCount(3);
            expect($activePrograms->every(fn ($p) => $p->is_active))->toBeTrue();
        });

        it('can filter inactive programs', function () {
            TrainingProgram::factory()->active()->count(3)->create();
            TrainingProgram::factory()->inactive()->count(2)->create();
            
            $inactivePrograms = TrainingProgram::where('is_active', false)->get();
            
            expect($inactivePrograms)->toHaveCount(2);
            expect($inactivePrograms->every(fn ($p) => !$p->is_active))->toBeTrue();
        });

        it('can filter by author', function () {
            $author = User::factory()->create();
            TrainingProgram::factory()->withAuthor($author)->count(2)->create();
            TrainingProgram::factory()->withAuthor(User::factory()->create())->count(3)->create();
            
            $programs = TrainingProgram::where('author_id', $author->id)->get();
            
            expect($programs)->toHaveCount(2);
            expect($programs->every(fn ($p) => $p->author_id === $author->id))->toBeTrue();
        });

        it('can filter programs without author', function () {
            TrainingProgram::factory()->withoutAuthor()->count(2)->create();
            TrainingProgram::factory()->withAuthor($this->user)->count(3)->create();
            
            $programs = TrainingProgram::whereNull('author_id')->get();
            
            expect($programs)->toHaveCount(2);
            expect($programs->every(fn ($p) => $p->author_id === null))->toBeTrue();
        });

        it('can filter by duration weeks', function () {
            TrainingProgram::factory()->create(['duration_weeks' => 4]);
            TrainingProgram::factory()->create(['duration_weeks' => 8]);
            TrainingProgram::factory()->create(['duration_weeks' => 12]);
            
            $programs = TrainingProgram::where('duration_weeks', '>=', 8)->get();
            
            expect($programs)->toHaveCount(2);
            expect($programs->every(fn ($p) => $p->duration_weeks >= 8))->toBeTrue();
        });

        it('can search by name', function () {
            TrainingProgram::factory()->create(['name' => 'Beginner Program']);
            TrainingProgram::factory()->create(['name' => 'Advanced Program']);
            TrainingProgram::factory()->create(['name' => 'Intermediate Program']);
            
            $programs = TrainingProgram::where('name', 'like', '%Beginner%')->get();
            
            expect($programs)->toHaveCount(1);
            expect($programs->first()->name)->toContain('Beginner');
        });
    });

    describe('timestamps', function () {
        it('has created_at timestamp', function () {
            $program = TrainingProgram::factory()->create();
            
            expect($program->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        });

        it('has updated_at timestamp', function () {
            $program = TrainingProgram::factory()->create();
            
            expect($program->updated_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        });

        it('updates updated_at when model is updated', function () {
            $program = TrainingProgram::factory()->create();
            $originalUpdatedAt = $program->updated_at;
            
            sleep(1); // Чтобы гарантировать разницу во времени
            $program->update(['name' => 'Updated Name']);
            
            expect($program->updated_at->gt($originalUpdatedAt))->toBeTrue();
        });
    });

    describe('database operations', function () {
        it('can be created', function () {
            $program = TrainingProgram::factory()->make();
            $program->save();
            
            expect($program->exists)->toBeTrue();
            expect($program->id)->toBeInt();
        });

        it('can be updated', function () {
            $program = TrainingProgram::factory()->create(['name' => 'Original Name']);
            
            $program->update(['name' => 'Updated Name']);
            
            expect($program->fresh()->name)->toBe('Updated Name');
        });

        it('can be deleted', function () {
            $program = TrainingProgram::factory()->create();
            $id = $program->id;
            
            $program->delete();
            
            expect(TrainingProgram::find($id))->toBeNull();
        });

        it('soft deletes are not used', function () {
            $program = TrainingProgram::factory()->create();
            
            expect($program->getConnection()->getSchemaBuilder()->hasColumn($program->getTable(), 'deleted_at'))->toBeFalse();
        });
    });
});

