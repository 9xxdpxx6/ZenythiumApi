<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ExportRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'format' => ['required', 'string', Rule::in(['json', 'pdf'])],
            'type' => ['required', 'string', Rule::in(['detailed', 'structure'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'format.required' => 'Формат экспорта обязателен',
            'format.in' => 'Формат должен быть json или pdf',
            'type.required' => 'Тип экспорта обязателен',
            'type.in' => 'Тип должен быть detailed или structure',
        ];
    }
}

