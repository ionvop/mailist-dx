<?php

namespace App\Http\Requests\Charts;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChartMetadataRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:500'],
            'title_alias' => ['nullable', 'string', 'max:500'],
            'artist' => ['required', 'string', 'max:500'],
            'artist_alias' => ['nullable', 'string', 'max:500'],
            'artist_link' => ['nullable', 'url', 'max:500'],
            'illustrator' => ['nullable', 'string', 'max:500'],
            'illustrator_link' => ['nullable', 'url', 'max:500'],
            'note_designer' => ['nullable', 'string', 'max:500'],
            'audio_bpm' => ['nullable', 'string', 'max:50'],
            'tags' => ['nullable', 'string', 'max:500'],
            'info' => ['nullable', 'string', 'max:5000'],
            'description' => ['nullable', 'string', 'max:5000'],
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
            'title.required' => 'The song title is required.',
            'artist.required' => 'The artist name is required.',
            'artist_link.url' => 'The artist link must be a valid URL.',
            'illustrator_link.url' => 'The illustrator link must be a valid URL.',
        ];
    }
}
