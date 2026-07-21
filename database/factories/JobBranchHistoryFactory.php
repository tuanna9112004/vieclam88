<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Job;
use App\Models\JobBranchHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobBranchHistory>
 */
class JobBranchHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'from_branch_id' => null,
            'to_branch_id' => Branch::factory(),
            'reason' => null,
            'changed_by' => User::factory()->admin(),
        ];
    }
}
