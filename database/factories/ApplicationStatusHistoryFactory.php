<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationStatusHistory>
 */
class ApplicationStatusHistoryFactory extends Factory
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
            'from_stage' => null,
            'to_stage' => 'new',
            'close_reason' => null,
            'workflow_cycle' => 1,
            'changed_by' => null,
            'actor_type' => 'system',
            'note' => null,
            'metadata' => null,
        ];
    }
}
