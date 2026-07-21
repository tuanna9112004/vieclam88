<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = 'Công nhân '.fake()->jobTitle();

        return [
            'public_id' => (string) Str::ulid(),
            'company_id' => Company::factory(),
            'company_contact_id' => null,
            'owner_branch_id' => Branch::factory(),
            'code' => strtoupper(fake()->unique()->bothify('JOB-####')),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 999999),
            'employment_type' => 'full_time',
            'quantity' => null,
            'gender_requirement' => null,
            'min_age' => null,
            'max_age' => null,
            'education_requirement' => null,
            'experience_requirement' => null,
            'salary_min' => null,
            'salary_max' => null,
            'salary_base' => null,
            'salary_period' => 'month',
            'currency' => 'VND',
            'salary_description' => null,
            'job_description' => null,
            'requirements' => null,
            'benefits' => null,
            'application_documents' => null,
            'has_shuttle_bus' => false,
            'has_accommodation' => false,
            'has_meal_support' => false,
            'is_urgent' => false,
            'status' => 'draft',
            'created_by' => User::factory()->admin(),
        ];
    }
}
