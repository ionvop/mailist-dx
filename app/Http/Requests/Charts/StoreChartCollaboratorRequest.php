<?php

namespace App\Http\Requests\Charts;

use Illuminate\Foundation\Http\FormRequest;

class StoreChartCollaboratorRequest extends FormRequest
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
            'username' => ['required', 'string', 'exists:users,username'],
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
            'username.required' => 'Enter the username of the user you want to give edit access to.',
            'username.exists' => 'No user with that username could be found.',
        ];
    }
}
