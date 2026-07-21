<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\CandidateContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateContact>
 */
class CandidateContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $phone = '09'.fake()->unique()->numerify('########');

        return [
            'candidate_id' => Candidate::factory(),
            'type' => 'phone',
            'value' => $phone,
            'normalized_value' => $phone,
            'is_primary' => false,
            'is_verified' => false,
            'verified_at' => null,
            'is_active' => true,
        ];
    }
}
