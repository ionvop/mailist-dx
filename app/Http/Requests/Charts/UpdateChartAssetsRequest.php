<?php

namespace App\Http\Requests\Charts;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChartAssetsRequest extends FormRequest
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
            'art' => ['nullable', 'image', 'mimes:jpeg,png,webp,gif', 'max:10240'],
            'track' => ['nullable', 'file', 'mimes:mp3,ogg,wav,m4a', 'max:10240'],
            'pv' => ['nullable', 'file', 'mimes:mp4,webm', 'max:30720'],
            'remove_pv' => ['nullable', 'boolean'],
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
            'art.image' => 'The chart background must be an image.',
            'art.mimes' => 'The chart background must be a JPEG, PNG, WebP, or GIF file.',
            'art.max' => 'The chart background may not be larger than 10MB.',
            'track.mimes' => 'The chart track must be an MP3, OGG, WAV, or M4A file.',
            'track.max' => 'The chart track may not be larger than 10MB.',
            'pv.mimes' => 'The chart PV must be an MP4 or WebM file.',
            'pv.max' => 'The chart PV may not be larger than 30MB.',
        ];
    }
}
