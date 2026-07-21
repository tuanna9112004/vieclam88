<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationNote>
 */
class ApplicationNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'user_id' => User::factory(),
            'content' => fake()->sentence(),
            'edited_at' => null,
        ];
    }
}
