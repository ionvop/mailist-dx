<?php

namespace App\Http\Requests\Charts;

use App\Enums\ImprovementStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImprovementRequest extends FormRequest
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
            'status' => ['required', Rule::in([
                ImprovementStatus::Resolved->value,
                ImprovementStatus::Rejected->value,
            ])],
            'resolution_note' => ['required', 'string', 'max:5000'],
            'revision_id' => ['nullable', 'integer', 'exists:chart_difficulty_revisions,id'],
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
            'status.required' => 'Choose whether to resolve or reject the suggestion.',
            'status.in' => 'The suggestion can only be resolved or rejected.',
            'resolution_note.required' => 'Add a remark explaining your decision.',
            'resolution_note.max' => 'Your remark may not be longer than 5000 characters.',
            'revision_id.exists' => 'The selected history record could not be found.',
        ];
    }
}
