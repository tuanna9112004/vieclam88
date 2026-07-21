<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\CandidateDuplicateReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateDuplicateReview>
 */
class CandidateDuplicateReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'candidate_id' => Candidate::factory(),
            'suspected_candidate_id' => Candidate::factory(),
            'reason_code' => 'same_phone_missing_dob',
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ];
    }
}
