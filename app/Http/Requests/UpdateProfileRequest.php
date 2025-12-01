<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Форма запроса для обновления профиля пользователя
 * 
 * Содержит правила валидации для обновления профиля пользователя.
 * Проверяет корректность имени пользователя.
 */
final class UpdateProfileRequest extends FormRequest
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
     * - name: обязательная строка до 255 символов
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
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
            'name.required' => 'Имя пользователя обязательно.',
            'name.string' => 'Имя пользователя должно быть строкой.',
            'name.max' => 'Имя пользователя не может быть длиннее 255 символов.',
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

