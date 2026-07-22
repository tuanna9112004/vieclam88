<?php

namespace Tests\Feature\Hr\Candidate;

use App\Enums\CandidateDuplicateReviewStatus;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\CandidateDuplicateReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateDuplicateReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_duplicate_review_routes(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->admin()->create();

        $candidate = Candidate::factory()->create();
        $suspected = Candidate::factory()->create();
        $application = Application::factory()->create([
            'candidate_id' => $candidate->id,
            'needs_duplicate_review' => true,
        ]);

        $review = CandidateDuplicateReview::factory()->create([
            'application_id' => $application->id,
            'candidate_id' => $candidate->id,
            'suspected_candidate_id' => $suspected->id,
            'reason_code' => 'same_phone_missing_dob',
            'status' => 'pending',
        ]);

        // Staff access is forbidden
        $this->actingAs($staff)->get(route('hr.duplicate-reviews.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('hr.duplicate-reviews.show', $review))->assertForbidden();
        $this->actingAs($staff)->post(route('hr.duplicate-reviews.resolve', $review), [
            'status' => 'confirmed_same',
        ])->assertForbidden();

        // Admin access is allowed
        $this->actingAs($admin)->get(route('hr.duplicate-reviews.index'))->assertOk();
        $this->actingAs($admin)->get(route('hr.duplicate-reviews.show', $review))->assertOk();
    }

    public function test_admin_can_resolve_review_and_confirmed_same_does_not_auto_merge(): void
    {
        $admin = User::factory()->admin()->create();
        $candidate = Candidate::factory()->create(['status' => 'active', 'merged_into_candidate_id' => null]);
        $suspected = Candidate::factory()->create(['status' => 'active', 'merged_into_candidate_id' => null]);
        $application = Application::factory()->create([
            'candidate_id' => $candidate->id,
            'needs_duplicate_review' => true,
        ]);

        $review = CandidateDuplicateReview::factory()->create([
            'application_id' => $application->id,
            'candidate_id' => $candidate->id,
            'suspected_candidate_id' => $suspected->id,
            'reason_code' => 'same_phone_missing_dob',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('hr.duplicate-reviews.resolve', $review), [
                'status' => 'confirmed_same',
                'review_note' => 'Xác nhận là cùng 1 ứng viên, sẽ xem xét merge sau.',
            ])
            ->assertRedirect(route('hr.duplicate-reviews.index'))
            ->assertSessionHas('status', 'Đã xử lý nghi ngờ trùng lặp thành công.');

        $freshReview = $review->fresh();
        $this->assertSame(CandidateDuplicateReviewStatus::ConfirmedSame, $freshReview->status);
        $this->assertSame($admin->id, $freshReview->reviewed_by);
        $this->assertNotNull($freshReview->reviewed_at);
        $this->assertSame('Xác nhận là cùng 1 ứng viên, sẽ xem xét merge sau.', $freshReview->review_note);

        // Crucial invariant check: confirmed_same MUST NOT auto-merge candidates
        $this->assertSame('active', $candidate->fresh()->status);
        $this->assertNull($candidate->fresh()->merged_into_candidate_id);
        $this->assertSame('active', $suspected->fresh()->status);
        $this->assertNull($suspected->fresh()->merged_into_candidate_id);
    }

    public function test_cannot_resolve_status_to_pending(): void
    {
        $admin = User::factory()->admin()->create();
        $review = CandidateDuplicateReview::factory()->create(['status' => 'pending']);

        $this->actingAs($admin)
            ->post(route('hr.duplicate-reviews.resolve', $review), [
                'status' => 'pending',
            ])
            ->assertSessionHasErrors('status');
    }

    public function test_application_needs_duplicate_review_resets_only_when_no_pending_reviews_remain(): void
    {
        $admin = User::factory()->admin()->create();
        $candidate = Candidate::factory()->create();
        $suspected1 = Candidate::factory()->create();
        $suspected2 = Candidate::factory()->create();

        $application = Application::factory()->create([
            'candidate_id' => $candidate->id,
            'needs_duplicate_review' => true,
        ]);

        $review1 = CandidateDuplicateReview::factory()->create([
            'application_id' => $application->id,
            'candidate_id' => $candidate->id,
            'suspected_candidate_id' => $suspected1->id,
            'reason_code' => 'same_phone_missing_dob',
            'status' => 'pending',
        ]);

        $review2 = CandidateDuplicateReview::factory()->create([
            'application_id' => $application->id,
            'candidate_id' => $candidate->id,
            'suspected_candidate_id' => $suspected2->id,
            'reason_code' => 'same_phone_different_name',
            'status' => 'pending',
        ]);

        // Resolve first review -> 1 pending review remains -> needs_duplicate_review must stay true
        $this->actingAs($admin)
            ->post(route('hr.duplicate-reviews.resolve', $review1), [
                'status' => 'confirmed_distinct',
                'review_note' => 'Xác nhận khác nhau.',
            ])
            ->assertRedirect();

        $this->assertTrue($application->fresh()->needs_duplicate_review);
        $this->assertNull($application->fresh()->duplicate_reviewed_at);
        $this->assertNull($application->fresh()->duplicate_reviewed_by);

        // Resolve second review -> 0 pending reviews remain -> needs_duplicate_review becomes false
        $this->actingAs($admin)
            ->post(route('hr.duplicate-reviews.resolve', $review2), [
                'status' => 'dismissed',
                'review_note' => 'Bỏ qua cảnh báo.',
            ])
            ->assertRedirect();

        $freshApp = $application->fresh();
        $this->assertFalse($freshApp->needs_duplicate_review);
        $this->assertNotNull($freshApp->duplicate_reviewed_at);
        $this->assertSame($admin->id, $freshApp->duplicate_reviewed_by);
    }
}
