<?php

namespace Database\Factories;

use App\Enums\ChartStatus;
use App\Models\Chart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chart>
 */
class ChartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uploader_id' => User::factory(),
            'title' => fake()->sentence(3),
            'artist' => fake()->name(),
            'audio_bpm' => (string) fake()->numberBetween(80, 220),
            'status' => ChartStatus::Wip,
            'is_private' => false,
        ];
    }

    /**
     * Indicate that the chart is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_private' => true,
        ]);
    }

    /**
     * Indicate the chart's status.
     */
    public function status(ChartStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
