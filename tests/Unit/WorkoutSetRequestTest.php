<?php

declare(strict_types=1);

use App\Http\Requests\WorkoutSetRequest;
use App\Models\Cycle;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Plan;
use App\Models\PlanExercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutSet;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    $this->plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
    $this->muscleGroup = MuscleGroup::factory()->create();
    $this->exercise = Exercise::factory()->create(['muscle_group_id' => $this->muscleGroup->id]);
    $this->planExercise = PlanExercise::factory()->create([
        'plan_id' => $this->plan->id,
        'exercise_id' => $this->exercise->id,
    ]);
    $this->workout = Workout::factory()->create([
        'plan_id' => $this->plan->id,
        'user_id' => $this->user->id,
    ]);
});

describe('WorkoutSetRequest', function () {
    describe('validation rules', function () {
        it('validates required fields', function () {
            $request = new WorkoutSetRequest();
            $rules = $request->rules();
            
            expect($rules)->toHaveKey('workout_id');
            expect($rules)->toHaveKey('plan_exercise_id');
            expect($rules['workout_id'])->toContain('required');
            expect($rules['plan_exercise_id'])->toContain('required');
        });

        it('validates workout_id is integer and exists', function () {
            $request = new WorkoutSetRequest();
            $rules = $request->rules();
            
            expect($rules['workout_id'])->toContain('integer');
            expect($rules['workout_id'])->toContain('exists:workouts,id');
        });

        it('validates plan_exercise_id is integer and exists', function () {
            $request = new WorkoutSetRequest();
            $rules = $request->rules();
            
            expect($rules['plan_exercise_id'])->toContain('integer');
            expect($rules['plan_exercise_id'])->toContain('exists:plan_exercises,id');
        });

        it('validates weight is nullable numeric with range', function () {
            $request = new WorkoutSetRequest();
            $rules = $request->rules();
            
            expect($rules['weight'])->toContain('nullable');
            expect($rules['weight'])->toContain('numeric');
            expect($rules['weight'])->toContain('min:0');
            expect($rules['weight'])->toContain('max:999.99');
        });

        it('validates reps is nullable integer with range', function () {
            $request = new WorkoutSetRequest();
            $rules = $request->rules();
            
            expect($rules['reps'])->toContain('nullable');
            expect($rules['reps'])->toContain('integer');
            expect($rules['reps'])->toContain('min:0');
            expect($rules['reps'])->toContain('max:9999');
        });
    });

    describe('validation messages', function () {
        it('has custom validation messages', function () {
            $request = new WorkoutSetRequest();
            $messages = $request->messages();
            
            expect($messages)->toHaveKey('workout_id.required');
            expect($messages)->toHaveKey('plan_exercise_id.required');
            expect($messages)->toHaveKey('weight.numeric');
            expect($messages)->toHaveKey('reps.integer');
        });

        it('has Russian validation messages', function () {
            $request = new WorkoutSetRequest();
            $messages = $request->messages();
            
            expect($messages['workout_id.required'])->toBe('Тренировка обязательна.');
            expect($messages['plan_exercise_id.required'])->toBe('Упражнение плана обязательно.');
            expect($messages['weight.numeric'])->toBe('Вес должен быть числом.');
            expect($messages['reps.integer'])->toBe('Количество повторений должно быть целым числом.');
        });
    });

    describe('authorization', function () {
        it('allows all requests', function () {
            $request = new WorkoutSetRequest();
            
            expect($request->authorize())->toBeTrue();
        });
    });

    describe('prepareForValidation', function () {
        it('adds user_id to request data', function () {
            $request = new WorkoutSetRequest();
            $request->setUserResolver(fn() => $this->user);
            $request->merge(['workout_id' => $this->workout->id]);
            
            // Вызываем prepareForValidation через рефлексию
            $reflection = new ReflectionClass($request);
            $method = $reflection->getMethod('prepareForValidation');
            $method->setAccessible(true);
            $method->invoke($request);
            
            expect($request->input('user_id'))->toBe($this->user->id);
        });
    });

    describe('withValidator', function () {
        it('validates workout belongs to user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);
            
            $request = new WorkoutSetRequest();
            $request->setUserResolver(fn() => $this->user);
            $request->merge([
                'workout_id' => $otherWorkout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'user_id' => $this->user->id,
            ]);
            
            $validator = \Illuminate\Support\Facades\Validator::make(
                $request->all(),
                $request->rules(),
                $request->messages()
            );
            
            $request->withValidator($validator);
            
            expect($validator->errors()->has('workout_id'))->toBeTrue();
            expect($validator->errors()->first('workout_id'))
                ->toBe('Тренировка не принадлежит текущему пользователю.');
        });

        it('validates plan_exercise belongs to same plan as workout', function () {
            $otherPlan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            $otherPlanExercise = PlanExercise::factory()->create([
                'plan_id' => $otherPlan->id,
                'exercise_id' => $this->exercise->id,
            ]);
            
            $request = new WorkoutSetRequest();
            $request->setUserResolver(fn() => $this->user);
            $request->merge([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $otherPlanExercise->id,
                'user_id' => $this->user->id,
            ]);
            
            $validator = \Illuminate\Support\Facades\Validator::make(
                $request->all(),
                $request->rules(),
                $request->messages()
            );
            
            $request->withValidator($validator);
            
            expect($validator->errors()->has('plan_exercise_id'))->toBeTrue();
            expect($validator->errors()->first('plan_exercise_id'))
                ->toBe('Упражнение должно принадлежать тому же плану, что и тренировка.');
        });

        it('passes validation when workout belongs to user and plan_exercise belongs to same plan', function () {
            $request = new WorkoutSetRequest();
            $request->setUserResolver(fn() => $this->user);
            $request->merge([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'user_id' => $this->user->id,
            ]);
            
            $validator = \Illuminate\Support\Facades\Validator::make(
                $request->all(),
                $request->rules(),
                $request->messages()
            );
            
            $request->withValidator($validator);
            
            expect($validator->errors()->has('workout_id'))->toBeFalse();
            expect($validator->errors()->has('plan_exercise_id'))->toBeFalse();
        });
    });
});
