<?php

namespace App\Http\Requests\Charts;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChartDifficultySettingsRequest extends FormRequest
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
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'difficulty_value' => ['nullable', 'string', 'max:50'],
            'charter' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'difficulty_value.max' => 'The difficulty level may not be longer than 50 characters.',
            'charter.max' => 'The charter name may not be longer than 500 characters.',
        ];
    }
}
