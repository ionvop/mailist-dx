<?php

namespace Database\Factories;

use App\Models\ChartDifficulty;
use App\Models\ChartDifficultyRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChartDifficultyRevision>
 */
class ChartDifficultyRevisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'difficulty_id' => ChartDifficulty::factory(),
            'user_id' => User::factory(),
            'code' => '(120){1}1,2,3,4,',
            'is_original' => false,
        ];
    }

    /**
     * Indicate that the revision is the original (uploaded) version.
     */
    public function original(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_original' => true,
        ]);
    }
}
