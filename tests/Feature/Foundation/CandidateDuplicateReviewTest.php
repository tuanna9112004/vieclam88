<?php

namespace Tests\Feature\Foundation;

use App\Enums\CandidateDuplicateReviewReason;
use App\Enums\CandidateDuplicateReviewStatus;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\CandidateDuplicateReview;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateDuplicateReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_reviews_for_same_pair_and_reason_must_be_unique(): void
    {
        $candidate = Candidate::factory()->create();
        $suspected = Candidate::factory()->create();
        CandidateDuplicateReview::factory()->create([
            'candidate_id' => $candidate->id,
            'suspected_candidate_id' => $suspected->id,
            'reason_code' => 'same_phone_missing_dob',
            'status' => 'pending',
        ]);

        $this->expectException(QueryException::class);

        CandidateDuplicateReview::factory()->create([
            'candidate_id' => $candidate->id,
            'suspected_candidate_id' => $suspected->id,
            'reason_code' => 'same_phone_missing_dob',
            'status' => 'pending',
        ]);
    }

    public function test_non_pending_duplicates_for_same_pair_and_reason_are_allowed(): void
    {
        $candidate = Candidate::factory()->create();
        $suspected = Candidate::factory()->create();
        CandidateDuplicateReview::factory()->create([
            'candidate_id' => $candidate->id,
            'suspected_candidate_id' => $suspected->id,
            'reason_code' => 'same_phone_missing_dob',
            'status' => 'confirmed_same',
        ]);
        CandidateDuplicateReview::factory()->create([
            'candidate_id' => $candidate->id,
            'suspected_candidate_id' => $suspected->id,
            'reason_code' => 'same_phone_missing_dob',
            'status' => 'confirmed_same',
        ]);

        $this->assertSame(2, CandidateDuplicateReview::where('candidate_id', $candidate->id)->count());
    }

    public function test_reason_code_and_status_cast_to_backed_enum(): void
    {
        $review = CandidateDuplicateReview::factory()->create([
            'reason_code' => 'multiple_exact_matches',
            'status' => 'dismissed',
        ]);

        $this->assertSame(CandidateDuplicateReviewReason::MultipleExactMatches, $review->reason_code);
        $this->assertSame(CandidateDuplicateReviewStatus::Dismissed, $review->status);
    }

    public function test_status_defaults_to_pending(): void
    {
        $review = CandidateDuplicateReview::factory()->create();

        $this->assertSame(CandidateDuplicateReviewStatus::Pending, $review->status);
    }

    public function test_deleting_application_referenced_by_review_is_restricted(): void
    {
        $application = Application::factory()->create();
        CandidateDuplicateReview::factory()->create(['application_id' => $application->id]);

        $this->expectException(QueryException::class);

        $application->delete();
    }

    public function test_belongs_to_application_candidate_and_suspected_candidate(): void
    {
        $application = Application::factory()->create();
        $candidate = Candidate::factory()->create();
        $suspected = Candidate::factory()->create();
        $review = CandidateDuplicateReview::factory()->create([
            'application_id' => $application->id,
            'candidate_id' => $candidate->id,
            'suspected_candidate_id' => $suspected->id,
        ]);

        $this->assertTrue($review->application->is($application));
        $this->assertTrue($review->candidate->is($candidate));
        $this->assertTrue($review->suspectedCandidate->is($suspected));
    }
}
