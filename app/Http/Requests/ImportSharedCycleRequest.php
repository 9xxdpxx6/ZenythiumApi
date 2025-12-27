<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Форма запроса для импорта расшаренного цикла
 * 
 * Содержит правила валидации для импорта расшаренного цикла тренировок.
 * Проверяет корректность UUID share_id.
 */
final class ImportSharedCycleRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения запроса
     * 
     * @return bool True - импорт доступен авторизованным пользователям
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Подготовить данные для валидации
     * 
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Добавляем shareId из route параметра для валидации
        $this->merge([
            'shareId' => $this->route('shareId'),
        ]);
    }

    /**
     * Получить правила валидации для запроса
     * 
     * @return array Массив правил валидации:
     * - shareId: обязательный UUID
     */
    public function rules(): array
    {
        return [
            'shareId' => 'required|uuid',
        ];
    }

    /**
     * Получить пользовательские сообщения для ошибок валидации
     * 
     * @return array Массив сообщений об ошибках на русском языке
     */
    public function messages(): array
    {
        return [
            'shareId.required' => 'Идентификатор ссылки обязателен.',
            'shareId.uuid' => 'Идентификатор ссылки должен быть корректным UUID.',
        ];
    }

    /**
     * Обработать неудачную валидацию
     * 
     * @param Validator $validator
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
