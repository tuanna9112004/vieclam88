<?php

namespace Database\Factories;

use App\Models\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Candidate>
 */
class CandidateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'full_name' => fake()->name(),
            'date_of_birth' => null,
            'gender' => null,
            'current_administrative_unit_id' => null,
            'address_detail' => null,
            'education_level' => null,
            'experience_summary' => null,
            'preferred_shift' => null,
            'available_from' => null,
            'status' => 'active',
        ];
    }
}
