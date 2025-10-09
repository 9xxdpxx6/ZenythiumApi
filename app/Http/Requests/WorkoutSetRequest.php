<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class WorkoutSetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'workout_id' => [
                'required',
                'integer',
                'exists:workouts,id'
            ],
            'plan_exercise_id' => [
                'required',
                'integer',
                'exists:plan_exercises,id'
            ],
            'weight' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999.99'
            ],
            'reps' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999'
            ],
        ];

        // Для PUT запросов делаем поля необязательными
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['workout_id'][0] = 'sometimes';
            $rules['plan_exercise_id'][0] = 'sometimes';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'workout_id.required' => 'Тренировка обязательна.',
            'workout_id.integer' => 'Тренировка должна быть числом.',
            'workout_id.exists' => 'Выбранная тренировка не существует.',
            'plan_exercise_id.required' => 'Упражнение плана обязательно.',
            'plan_exercise_id.integer' => 'Упражнение плана должно быть числом.',
            'plan_exercise_id.exists' => 'Выбранное упражнение плана не существует.',
            'weight.numeric' => 'Вес должен быть числом.',
            'weight.min' => 'Вес не может быть отрицательным.',
            'weight.max' => 'Вес не может превышать 999.99.',
            'reps.integer' => 'Количество повторений должно быть целым числом.',
            'reps.min' => 'Количество повторений не может быть отрицательным.',
            'reps.max' => 'Количество повторений не может превышать 9999.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Дополнительная валидация для проверки принадлежности workout пользователю
        if ($this->has('workout_id')) {
            $this->merge([
                'user_id' => $this->user()->id,
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Проверяем, что workout принадлежит пользователю
            if ($this->has('workout_id') && $this->has('user_id')) {
                $workout = \App\Models\Workout::where('id', $this->workout_id)
                    ->where('user_id', $this->user_id)
                    ->first();
                
                if (!$workout) {
                    $validator->errors()->add('workout_id', 'Тренировка не принадлежит текущему пользователю.');
                }
            }

            // Проверяем, что plan_exercise принадлежит тому же плану, что и workout
            if ($this->has('workout_id') && $this->has('plan_exercise_id')) {
                $workout = \App\Models\Workout::find($this->workout_id);
                $planExercise = \App\Models\PlanExercise::find($this->plan_exercise_id);
                
                if ($workout && $planExercise && $workout->plan_id !== $planExercise->plan_id) {
                    $validator->errors()->add('plan_exercise_id', 'Упражнение должно принадлежать тому же плану, что и тренировка.');
                }
            }
        });
    }
}
