<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PlanExerciseRequest extends FormRequest
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
        $planId = $this->route('plan');
        $isUpdate = $this->route('planExercise') !== null;
        
        return [
            'exercise_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'exists:exercises,id',
                Rule::unique('plan_exercises')->where(function ($query) use ($planId) {
                    return $query->where('plan_id', $planId);
                })->ignore($this->route('planExercise'))
            ],
            'order' => [
                'sometimes',
                'integer',
                'min:1'
            ]
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
            'exercise_id.required' => 'ID упражнения обязателен',
            'exercise_id.integer' => 'ID упражнения должен быть числом',
            'exercise_id.exists' => 'Упражнение не найдено',
            'exercise_id.unique' => 'Это упражнение уже добавлено в план',
            'order.integer' => 'Порядок должен быть числом',
            'order.min' => 'Порядок должен быть больше 0'
        ];
    }
}
