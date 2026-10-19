<?php

namespace App\Http\Requests\Settings;

use App\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
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
            'preferences' => ['nullable', 'array'],
            'preferences.*' => ['boolean'],
        ];
    }

    /**
     * The validated preferences, restricted to known notification types.
     *
     * @return array<string, bool>
     */
    public function preferences(): array
    {
        $validated = $this->validated('preferences', []);

        return collect(NotificationType::cases())
            ->mapWithKeys(fn (NotificationType $type): array => [
                $type->value => (bool) ($validated[$type->value] ?? false),
            ])
            ->all();
    }
}
