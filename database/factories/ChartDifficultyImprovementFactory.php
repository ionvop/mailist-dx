<?php

namespace Database\Factories;

use App\Enums\ImprovementStatus;
use App\Models\ChartDifficulty;
use App\Models\ChartDifficultyImprovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChartDifficultyImprovement>
 */
class ChartDifficultyImprovementFactory extends Factory
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
            'status' => ImprovementStatus::Open,
        ];
    }

    /**
     * Attach a code snippet with a line range.
     */
    public function withSnippet(int $lineStart = 1, int $lineEnd = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'line_start' => $lineStart,
            'line_end' => $lineEnd,
            'snippet' => "1,2,3,\n4,5,6,\n7,8,9,",
        ]);
    }

    /**
     * Indicate that the suggestion has been resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImprovementStatus::Resolved,
            'resolution_note' => fake()->sentence(),
            'resolved_by' => User::factory(),
            'resolved_at' => now(),
        ]);
    }

    /**
     * Indicate that the suggestion has been rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImprovementStatus::Rejected,
            'resolution_note' => fake()->sentence(),
            'resolved_by' => User::factory(),
            'resolved_at' => now(),
        ]);
    }
}
