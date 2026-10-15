<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserComment>
 */
class UserCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_user_id' => User::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
        ];
    }
}
