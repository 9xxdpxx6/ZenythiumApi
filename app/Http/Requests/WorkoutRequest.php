<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Форма запроса для тренировок
 * 
 * Содержит правила валидации для создания и обновления тренировок.
 * Проверяет корректность данных плана, времени начала и окончания тренировки.
 */
final class WorkoutRequest extends FormRequest
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
     * - plan_id: обязательное целое число, должно существовать в таблице plans
     * - started_at: обязательная дата, не может быть в будущем
     * - finished_at: опциональная дата, должна быть после started_at
     */
    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'integer',
                'exists:plans,id'
            ],
            'started_at' => [
                'required',
                'date',
                'before_or_equal:now'
            ],
            'finished_at' => [
                'nullable',
                'date',
                'after_or_equal:started_at'
            ],
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
            'plan_id.required' => 'План обязателен.',
            'plan_id.integer' => 'План должен быть числом.',
            'plan_id.exists' => 'Выбранный план не существует.',
            'started_at.required' => 'Время начала обязательно.',
            'started_at.date' => 'Время начала должно быть корректной датой.',
            'started_at.before_or_equal' => 'Время начала не может быть в будущем.',
            'finished_at.date' => 'Время окончания должно быть корректной датой.',
            'finished_at.after_or_equal' => 'Время окончания должно быть позже или равно времени начала.',
        ];
    }

    /**
     * Prepare the data for validation.
     * 
     * Обрабатывает входящие даты без временной зоны, интерпретируя их как локальное время (Europe/Moscow).
     */
    protected function prepareForValidation(): void
    {
        $data = [
            'user_id' => $this->user()->id,
        ];

        // Обрабатываем started_at: если дата без зоны, интерпретируем как локальное время
        if ($this->has('started_at') && $this->started_at) {
            $data['started_at'] = $this->normalizeDateTime($this->started_at);
        }

        // Обрабатываем finished_at: если дата без зоны, интерпретируем как локальное время
        if ($this->has('finished_at') && $this->finished_at) {
            $data['finished_at'] = $this->normalizeDateTime($this->finished_at);
        }

        $this->merge($data);
    }

    /**
     * Нормализует дату/время, интерпретируя строки без зоны как локальное время (Europe/Moscow).
     * 
     * Возвращает строку в формате ISO 8601 с явным указанием временной зоны (+03:00 для МСК),
     * чтобы Laravel правильно интерпретировал её как локальное время.
     * 
     * @param string $dateTime Строка с датой/временем (может быть в формате YYYY-MM-DDTHH:mm или YYYY-MM-DD HH:mm:ss)
     * @return string Нормализованная строка с датой/временем в формате ISO 8601 с временной зоной
     */
    private function normalizeDateTime(string $dateTime): string
    {
        // Если строка уже содержит информацию о зоне (Z, +03:00, etc.), оставляем как есть
        if (preg_match('/[Z+-]\d{2}:?\d{2}$/', $dateTime)) {
            return $dateTime;
        }

        // Если строка без зоны, создаем Carbon в локальной зоне и возвращаем в формате ISO 8601 с зоной
        try {
            $carbon = \Carbon\Carbon::parse($dateTime, config('app.timezone'));
            // Возвращаем в формате ISO 8601 с явным указанием временной зоны (+03:00 для МСК)
            // Это гарантирует, что Laravel правильно интерпретирует время как локальное
            return $carbon->format('Y-m-d\TH:i:sP');
        } catch (\Exception $e) {
            // Если не удалось распарсить, возвращаем как есть (валидация поймает ошибку)
            return $dateTime;
        }
    }
}
