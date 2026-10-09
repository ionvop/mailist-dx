<?php

namespace Database\Factories;

use App\Enums\ChartDifficultyName;
use App\Models\Chart;
use App\Models\ChartDifficulty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChartDifficulty>
 */
class ChartDifficultyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chart_id' => Chart::factory(),
            'chart_index' => (string) fake()->unique()->numberBetween(1, 9),
            'difficulty' => ChartDifficultyName::Master,
            'difficulty_value' => (string) fake()->numberBetween(1, 15),
            'charter' => fake()->name(),
            'code' => '(120){1}1,2,3,4,',
            'ranked_rating' => 0,
        ];
    }

    /**
     * Indicate the difficulty of the chart.
     */
    public function difficulty(ChartDifficultyName $difficulty): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => $difficulty,
        ]);
    }
}
