<?php

namespace App\Http\Requests\Charts;

use Illuminate\Foundation\Http\FormRequest;

class StoreImprovementRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:5000'],
            'line_start' => ['nullable', 'integer', 'min:1'],
            'line_end' => ['nullable', 'integer', 'min:1', 'gte:line_start'],
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
            'body.required' => 'Please describe the improvement you are suggesting.',
            'body.max' => 'Your suggestion may not be longer than 5000 characters.',
            'line_end.gte' => 'The end line must be at or after the start line.',
        ];
    }
}
