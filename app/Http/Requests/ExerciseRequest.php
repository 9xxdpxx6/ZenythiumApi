<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Форма запроса для упражнений
 * 
 * Содержит правила валидации для создания и обновления упражнений.
 * Проверяет уникальность названия упражнения для пользователя и корректность группы мышц.
 */
final class ExerciseRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения запроса
     * 
     * @return bool True - пользователь авторизован
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Получить правила валидации для запроса
     * 
     * @return array Массив правил валидации:
     * - name: обязательная строка до 255 символов, уникальная для пользователя
     * - description: опциональная строка до 1000 символов
     * - muscle_group_id: обязательное целое число, должно существовать в таблице muscle_groups
     * - is_active: опциональное булево значение
     */
    public function rules(): array
    {
        $exerciseId = $this->route('id');
        $userId = $this->user()->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:exercises,name,' . $exerciseId . ',id,user_id,' . $userId
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'muscle_group_id' => [
                'required',
                'integer',
                'exists:muscle_groups,id'
            ],
            'is_active' => [
                'boolean'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Название упражнения обязательно.',
            'name.string' => 'Название упражнения должно быть строкой.',
            'name.max' => 'Название упражнения не может быть длиннее 255 символов.',
            'name.unique' => 'Упражнение с таким названием уже существует.',
            'description.string' => 'Описание должно быть строкой.',
            'description.max' => 'Описание не может быть длиннее 1000 символов.',
            'muscle_group_id.required' => 'Группа мышц обязательна.',
            'muscle_group_id.integer' => 'Группа мышц должна быть числом.',
            'muscle_group_id.exists' => 'Выбранная группа мышц не существует.',
            'is_active.boolean' => 'Статус активности должен быть true или false.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => $this->user()->id,
        ]);
    }
}
