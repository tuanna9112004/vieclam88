<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationAppointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationAppointment>
 */
class ApplicationAppointmentFactory extends Factory
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
            'type' => 'callback',
            'scheduled_at' => now()->addDay(),
            'location_detail' => null,
            'status' => 'scheduled',
            'outcome' => null,
            'note' => null,
            'workflow_cycle' => 1,
            'created_by' => User::factory(),
            'completed_by' => null,
            'completed_at' => null,
        ];
    }
}
