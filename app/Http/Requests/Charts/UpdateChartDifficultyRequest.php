<?php

namespace App\Http\Requests\Charts;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateChartDifficultyRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:500000', $this->rejectMaidataKeys()],
        ];
    }

    /**
     * Reject code containing a line that would be read as a `&key=value` pair.
     *
     * The code is written verbatim into the stored maidata file, so a line
     * beginning with `&` could inject or overwrite other maidata headers.
     */
    private function rejectMaidataKeys(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (preg_match('/^&/m', (string) $value) === 1) {
                $fail('The chart code may not contain a line beginning with "&".');
            }
        };
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'The chart code is required.',
            'code.max' => 'The chart code is too long.',
        ];
    }
}
