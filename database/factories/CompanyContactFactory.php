<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyContact>
 */
class CompanyContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'position' => null,
            'phone' => '09'.fake()->numerify('########'),
            'phone_normalized' => null,
            'zalo' => null,
            'email' => null,
            'is_primary' => false,
            'is_public' => false,
            'status' => 'active',
        ];
    }
}
