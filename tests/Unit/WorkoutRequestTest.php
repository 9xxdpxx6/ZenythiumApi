<?php

declare(strict_types=1);

use App\Http\Requests\WorkoutRequest;
use App\Models\Cycle;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Support\Facades\Validator;
use ReflectionClass;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    $this->plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
    $this->workout = Workout::factory()->create([
        'plan_id' => $this->plan->id,
        'user_id' => $this->user->id,
    ]);
});

dataset('required_fields', [
    'plan_id' => ['plan_id'],
    'started_at' => ['started_at'],
]);

dataset('invalid_dates', [
    'invalid_format' => ['invalid-date'],
    'future_date' => [now()->addDay()->format('Y-m-d H:i:s')],
]);

dataset('valid_dates', [
    'past_date' => [now()->subDay()->format('Y-m-d H:i:s')],
    'current_date' => [now()->format('Y-m-d H:i:s')],
]);

describe('WorkoutRequest', function () {
    describe('validation rules', function () {
        it('passes validation with valid data', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => '2024-03-15 10:00:00',
                'finished_at' => '2024-03-15 11:30:00',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });

        it('passes validation with minimal required data', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => '2024-03-15 10:00:00',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });

        it('fails validation without required field', function ($field) {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => '2024-03-15 10:00:00',
                'finished_at' => '2024-03-15 11:30:00',
            ];
            
            unset($data[$field]);
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has($field))->toBeTrue();
        })->with('required_fields');
    });

    describe('plan_id validation', function () {
        it('fails validation with non-existent plan', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => 999,
                'started_at' => '2024-03-15 10:00:00',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('plan_id'))->toBeTrue();
        });

        it('fails validation with non-integer plan_id', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => 'not-a-number',
                'started_at' => '2024-03-15 10:00:00',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('plan_id'))->toBeTrue();
        });

        it('passes validation with valid plan_id', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => '2024-03-15 10:00:00',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });
    });

    describe('started_at validation', function () {
        it('fails validation with invalid date format', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => 'invalid-date',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('started_at'))->toBeTrue();
        });

        it('fails validation with future date', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('started_at'))->toBeTrue();
        });

        it('passes validation with past date', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => now()->subDay()->format('Y-m-d H:i:s'),
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });

        it('passes validation with current date', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => now()->format('Y-m-d H:i:s'),
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });
    });

    describe('finished_at validation', function () {
        it('passes validation when finished_at is null', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => '2024-03-15 10:00:00',
                'finished_at' => null,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });

        it('fails validation with invalid date format', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => '2024-03-15 10:00:00',
                'finished_at' => 'invalid-date',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('finished_at'))->toBeTrue();
        });

        it('fails validation when finished_at is before started_at', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => '2024-03-15 11:00:00',
                'finished_at' => '2024-03-15 10:00:00',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('finished_at'))->toBeTrue();
        });

        it('passes validation when finished_at equals started_at', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => '2024-03-15 10:00:00',
                'finished_at' => '2024-03-15 10:00:00',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });

        it('passes validation when finished_at is after started_at', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => '2024-03-15 10:00:00',
                'finished_at' => '2024-03-15 11:30:00',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });
    });

    describe('custom messages', function () {
        it('returns correct custom messages', function () {
            $request = new WorkoutRequest();
            $messages = $request->messages();
            
            expect($messages)->toBeArray();
            expect($messages['plan_id.required'])->toBe('План обязателен.');
            expect($messages['plan_id.integer'])->toBe('План должен быть числом.');
            expect($messages['plan_id.exists'])->toBe('Выбранный план не существует.');
            expect($messages['started_at.required'])->toBe('Время начала обязательно.');
            expect($messages['started_at.date'])->toBe('Время начала должно быть корректной датой.');
            expect($messages['started_at.before_or_equal'])->toBe('Время начала не может быть в будущем.');
            expect($messages['finished_at.date'])->toBe('Время окончания должно быть корректной датой.');
            expect($messages['finished_at.after_or_equal'])->toBe('Время окончания должно быть позже или равно времени начала.');
        });
    });

    describe('authorization', function () {
        it('authorizes all requests', function () {
            $request = new WorkoutRequest();
            
            expect($request->authorize())->toBeTrue();
        });
    });

    describe('prepareForValidation', function () {
        it('automatically sets user_id from authenticated user', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => '2024-03-15 10:00:00',
            ];
            
            $request->merge($data);
            
            // Call prepareForValidation using reflection since it's protected
            $reflection = new ReflectionClass($request);
            $method = $reflection->getMethod('prepareForValidation');
            $method->setAccessible(true);
            $method->invoke($request);
            
            expect($request->input('user_id'))->toBe($this->user->id);
        });
    });

    describe('edge cases', function () {
        it('handles empty finished_at string', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => '2024-03-15 10:00:00',
                'finished_at' => '',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });

        it('handles zero plan_id', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => 0,
                'started_at' => '2024-03-15 10:00:00',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('plan_id'))->toBeTrue();
        });

        it('handles negative plan_id', function () {
            $request = new WorkoutRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'plan_id' => -1,
                'started_at' => '2024-03-15 10:00:00',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('plan_id'))->toBeTrue();
        });
    });
});
