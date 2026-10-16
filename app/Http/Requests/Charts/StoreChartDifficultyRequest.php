<?php

namespace App\Http\Requests\Charts;

use App\Enums\ChartDifficultyName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChartDifficultyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization (chart ownership) is enforced by the controller so that
     * non-owners get the same 404 convention as the other difficulty routes.
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
        $existing = $this->route('chart')?->difficulties
            ->pluck('difficulty')
            ->map(fn (ChartDifficultyName $name) => $name->value)
            ->all();

        $types = collect(ChartDifficultyName::cases())
            ->reject(fn (ChartDifficultyName $name) => $name === ChartDifficultyName::Custom)
            ->map(fn (ChartDifficultyName $name) => $name->value)
            ->values()
            ->all();

        return [
            'difficulty' => [
                'required',
                'string',
                Rule::in($types),
                Rule::notIn($existing),
            ],
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
            'difficulty.in' => 'Choose a difficulty type between Easy and Utage. Custom difficulties cannot be added here.',
            'difficulty.not_in' => 'That difficulty has already been added to this chart.',
        ];
    }
}
