<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GoalStatus;
use App\Enums\GoalType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Форма запроса для обновления цели
 */
final class UpdateGoalRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения запроса
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Получить правила валидации для запроса
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $exerciseTypes = [
            GoalType::EXERCISE_MAX_WEIGHT->value,
            GoalType::EXERCISE_MAX_REPS->value,
            GoalType::EXERCISE_VOLUME->value,
        ];

        // Получаем новый тип из запроса или текущий тип цели
        $goalType = $this->input('type');
        if (!$goalType) {
            // Если тип не передается, получаем текущий тип цели из базы
            $goalId = $this->route('id');
            if ($goalId) {
                $goal = \App\Models\Goal::find($goalId);
                $goalType = $goal?->type?->value;
            }
        }

        return [
            'type' => [
                'nullable',
                'string',
                Rule::enum(GoalType::class),
            ],
            'title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'target_value' => [
                'nullable',
                'numeric',
                'min:0.01',
            ],
            'end_date' => [
                'nullable',
                'date',
            ],
            'exercise_id' => [
                Rule::requiredIf($goalType && in_array($goalType, $exerciseTypes, true)),
                'nullable',
                'integer',
                'exists:exercises,id',
            ],
            'status' => [
                'nullable',
                'string',
                Rule::enum(GoalStatus::class),
            ],
        ];
    }

    /**
     * Получить пользовательские сообщения для ошибок валидации
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.enum' => 'Недопустимый тип цели.',
            'title.string' => 'Название должно быть строкой.',
            'title.max' => 'Название не должно превышать 255 символов.',
            'target_value.numeric' => 'Целевое значение должно быть числом.',
            'target_value.min' => 'Целевое значение должно быть больше 0.',
            'end_date.date' => 'Дата окончания должна быть корректной датой.',
            'exercise_id.required' => 'Упражнение обязательно для данного типа цели.',
            'exercise_id.exists' => 'Выбранное упражнение не существует.',
            'status.enum' => 'Недопустимый статус цели.',
        ];
    }
}
