<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'public_id' => (string) Str::ulid(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 999999),
            'short_name' => null,
            'description' => null,
            'logo_path' => null,
            'cover_path' => null,
            'industry' => null,
            'website' => null,
            'is_verified' => false,
            'status' => 'active',
            'created_by' => User::factory()->admin(),
            'updated_by' => null,
        ];
    }
}
