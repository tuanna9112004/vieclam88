<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationContactAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationContactAttempt>
 */
class ApplicationContactAttemptFactory extends Factory
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
            'contacted_by' => User::factory(),
            'channel' => 'phone',
            'result' => 'reached',
            'workflow_cycle' => 1,
            'contacted_at' => now(),
            'note' => null,
        ];
    }
}
