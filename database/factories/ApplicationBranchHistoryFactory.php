<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationBranchHistory;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationBranchHistory>
 */
class ApplicationBranchHistoryFactory extends Factory
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
            'from_branch_id' => null,
            'to_branch_id' => Branch::factory(),
            'transferred_by' => null,
            'reason' => null,
        ];
    }
}
