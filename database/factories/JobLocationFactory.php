<?php

namespace Database\Factories;

use App\Models\CompanyLocation;
use App\Models\Job;
use App\Models\JobLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobLocation>
 */
class JobLocationFactory extends Factory
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
            'company_location_id' => CompanyLocation::factory(),
            'is_primary' => false,
        ];
    }
}
