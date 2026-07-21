<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\JobVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobVerification>
 */
class JobVerificationFactory extends Factory
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
            'verified_by' => User::factory()->admin(),
            'result' => 'still_open',
            'note' => null,
            'verified_at' => now(),
        ];
    }
}
