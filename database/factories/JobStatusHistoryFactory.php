<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\JobStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobStatusHistory>
 */
class JobStatusHistoryFactory extends Factory
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
            'from_status' => 'draft',
            'to_status' => 'published',
            'reason' => null,
            'changed_by' => User::factory()->admin(),
        ];
    }
}
