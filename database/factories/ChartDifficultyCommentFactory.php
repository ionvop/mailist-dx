<?php

namespace Database\Factories;

use App\Models\ChartDifficulty;
use App\Models\ChartDifficultyComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChartDifficultyComment>
 */
class ChartDifficultyCommentFactory extends Factory
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
            'body' => fake()->sentence(),
        ];
    }
}
