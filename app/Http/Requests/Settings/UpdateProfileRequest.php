<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        $user = $this->user();

        $rules = [
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,webp,gif', 'max:2048'],
            'bio' => ['nullable', 'string', 'max:5000'],
        ];

        // Only allow a username change once the cooldown has elapsed.
        if ($user instanceof User && $user->canChangeUsername()) {
            $rules['username'] = [
                'nullable',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('users', 'username')->ignore($user->id),
            ];
        }

        return $rules;
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.regex' => 'Usernames may only contain letters, numbers, hyphens, and underscores.',
            'avatar.image' => 'The avatar must be an image.',
            'avatar.mimes' => 'The avatar must be a JPEG, PNG, WebP, or GIF file.',
            'avatar.max' => 'The avatar may not be larger than 2MB.',
        ];
    }
}
