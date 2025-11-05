<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Форма запроса для регистрации пользователя
 * 
 * Содержит правила валидации для регистрации нового пользователя.
 * Проверяет уникальность email и корректность данных регистрации.
 */
final class RegisterRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения запроса
     * 
     * @return bool True - регистрация доступна всем
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
     * - email: обязательный email до 255 символов, уникальный в таблице users
     * - password: обязательная строка минимум 8 символов, требует подтверждения
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
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
            'email.required' => 'Email обязателен.',
            'email.string' => 'Email должен быть строкой.',
            'email.email' => 'Email должен быть корректным email адресом.',
            'email.max' => 'Email не может быть длиннее 255 символов.',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован.',
            'password.required' => 'Пароль обязателен.',
            'password.string' => 'Пароль должен быть строкой.',
            'password.min' => 'Пароль должен содержать минимум 8 символов.',
            'password.confirmed' => 'Пароль и подтверждение пароля не совпадают.',
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
