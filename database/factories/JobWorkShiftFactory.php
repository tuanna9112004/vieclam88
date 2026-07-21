<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\JobWorkShift;
use App\Models\WorkShift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobWorkShift>
 */
class JobWorkShiftFactory extends Factory
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
            'work_shift_id' => WorkShift::factory(),
            'description' => null,
        ];
    }
}
