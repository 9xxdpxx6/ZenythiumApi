<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ExerciseRequest extends FormRequest
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
        $exerciseId = $this->route('exercise')?->id;
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
            'name.required' => 'The exercise name is required.',
            'name.string' => 'The exercise name must be a string.',
            'name.max' => 'The exercise name may not be greater than 255 characters.',
            'name.unique' => 'The exercise name has already been taken.',
            'description.string' => 'The description must be a string.',
            'description.max' => 'The description may not be greater than 1000 characters.',
            'muscle_group_id.required' => 'The muscle group is required.',
            'muscle_group_id.integer' => 'The muscle group must be an integer.',
            'muscle_group_id.exists' => 'The selected muscle group does not exist.',
            'is_active.boolean' => 'The active status must be true or false.',
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
