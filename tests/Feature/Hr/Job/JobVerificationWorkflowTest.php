<?php

namespace Tests\Feature\Hr\Job;

use App\Actions\Job\SaveJobVerificationAction;
use App\Models\Branch;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class JobVerificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    // --- draft ---

    public function test_draft_job_accepts_still_open_and_keeps_draft(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id, 'status' => 'draft']);

        $response = $this->actingAs($staff)->post(route('hr.jobs.verify', $job), ['result' => 'still_open']);

        $response->assertRedirect(route('hr.jobs.index'));
        $job->refresh();
        $this->assertSame('draft', $job->status);
        $this->assertNotNull($job->last_checked_at);
        $this->assertNotNull($job->last_verified_at);
        $this->assertDatabaseCount('job_verifications', 1);
    }

    public function test_draft_job_accepts_needs_review_and_keeps_draft_without_verified_at(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id, 'status' => 'draft']);

        $response = $this->actingAs($staff)->post(route('hr.jobs.verify', $job), ['result' => 'needs_review']);

        $response->assertRedirect(route('hr.jobs.index'));
        $job->refresh();
        $this->assertSame('draft', $job->status);
        $this->assertNotNull($job->last_checked_at);
        $this->assertNull($job->last_verified_at);
    }

    public function test_draft_job_rejects_paused_result(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id, 'status' => 'draft']);

        $response = $this->actingAs($staff)->post(route('hr.jobs.verify', $job), ['result' => 'paused']);

        $response->assertSessionHasErrors('result');
        $this->assertDatabaseCount('job_verifications', 0);
    }

    public function test_draft_job_rejects_closed_result(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id, 'status' => 'draft']);

        $response = $this->actingAs($staff)->post(route('hr.jobs.verify', $job), ['result' => 'closed']);

        $response->assertSessionHasErrors('result');
        $this->assertDatabaseCount('job_verifications', 0);
    }

    // --- published ---

    public function test_published_job_still_open_updates_both_marks_and_keeps_published(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id, 'status' => 'published']);

        $this->actingAs($staff)->post(route('hr.jobs.verify', $job), ['result' => 'still_open']);

        $job->refresh();
        $this->assertSame('published', $job->status);
        $this->assertNotNull($job->last_checked_at);
        $this->assertNotNull($job->last_verified_at);
        $this->assertDatabaseCount('job_status_histories', 0);
    }

    public function test_published_job_needs_review_only_updates_last_checked_at(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id, 'status' => 'published']);

        $this->actingAs($staff)->post(route('hr.jobs.verify', $job), ['result' => 'needs_review']);

        $job->refresh();
        $this->assertSame('published', $job->status);
        $this->assertNotNull($job->last_checked_at);
        $this->assertNull($job->last_verified_at);
        $this->assertDatabaseCount('job_status_histories', 0);
    }

    public function test_published_job_paused_result_transitions_to_paused_with_history(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id, 'status' => 'published']);

        $this->actingAs($staff)->post(route('hr.jobs.verify', $job), ['result' => 'paused', 'note' => 'Công ty tạm ngừng tuyển']);

        $job->refresh();
        $this->assertSame('paused', $job->status);
        $this->assertDatabaseCount('job_status_histories', 1);
        $this->assertDatabaseHas('job_status_histories', [
            'job_id' => $job->id,
            'from_status' => 'published',
            'to_status' => 'paused',
        ]);
    }

    public function test_published_job_closed_result_transitions_to_closed_with_history_and_requires_note(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id, 'status' => 'published']);

        $rejected = $this->actingAs($staff)->post(route('hr.jobs.verify', $job), ['result' => 'closed']);
        $rejected->assertSessionHasErrors('note');
        $this->assertSame('published', $job->fresh()->status);

        $accepted = $this->actingAs($staff)->post(route('hr.jobs.verify', $job), [
            'result' => 'closed',
            'note' => 'Công ty đã tuyển đủ',
        ]);
        $accepted->assertRedirect(route('hr.jobs.index'));

        $job->refresh();
        $this->assertSame('closed', $job->status);
        $this->assertNotNull($job->closed_at);
        $this->assertDatabaseHas('job_status_histories', [
            'job_id' => $job->id,
            'from_status' => 'published',
            'to_status' => 'closed',
            'reason' => 'Công ty đã tuyển đủ',
        ]);
    }

    // --- paused ---

    public function test_paused_job_still_open_updates_marks_and_stays_paused_without_history(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id, 'status' => 'paused']);

        $this->actingAs($staff)->post(route('hr.jobs.verify', $job), ['result' => 'still_open']);

        $job->refresh();
        $this->assertSame('paused', $job->status);
        $this->assertNotNull($job->last_verified_at);
        $this->assertDatabaseCount('job_status_histories', 0);
    }

    public function test_paused_job_verified_paused_again_creates_no_status_history(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id, 'status' => 'paused']);

        $this->actingAs($staff)->post(route('hr.jobs.verify', $job), ['result' => 'paused']);

        $job->refresh();
        $this->assertSame('paused', $job->status);
        $this->assertDatabaseCount('job_verifications', 1);
        $this->assertDatabaseCount('job_status_histories', 0);
    }

    public function test_paused_job_closed_result_transitions_to_closed(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id, 'status' => 'paused']);

        $this->actingAs($staff)->post(route('hr.jobs.verify', $job), [
            'result' => 'closed',
            'note' => 'Nhà máy dừng hoạt động',
        ]);

        $job->refresh();
        $this->assertSame('closed', $job->status);
        $this->assertDatabaseHas('job_status_histories', [
            'job_id' => $job->id,
            'from_status' => 'paused',
            'to_status' => 'closed',
        ]);
    }

    // --- closed ---

    public function test_closed_job_rejects_any_verification(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id, 'status' => 'closed']);

        $response = $this->actingAs($staff)->post(route('hr.jobs.verify', $job), ['result' => 'still_open']);

        $response->assertForbidden();
        $this->assertDatabaseCount('job_verifications', 0);
    }

    public function test_admin_cannot_verify_closed_job_either(): void
    {
        $admin = User::factory()->admin()->create();
        $job = Job::factory()->create(['status' => 'closed']);

        $response = $this->actingAs($admin)->post(route('hr.jobs.verify', $job), ['result' => 'still_open']);

        $response->assertForbidden();
    }

    // --- authorization / branch isolation ---

    public function test_staff_cannot_verify_job_of_other_branch(): void
    {
        $staff = User::factory()->create();
        $otherBranch = Branch::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $otherBranch->id, 'status' => 'published']);

        $response = $this->actingAs($staff)->post(route('hr.jobs.verify', $job), ['result' => 'still_open']);

        $response->assertForbidden();
        $this->assertDatabaseCount('job_verifications', 0);
    }

    public function test_admin_can_verify_job_of_any_branch(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $branch->id, 'status' => 'published']);

        $response = $this->actingAs($admin)->post(route('hr.jobs.verify', $job), ['result' => 'still_open']);

        $response->assertRedirect(route('hr.jobs.index'));
        $this->assertDatabaseCount('job_verifications', 1);
    }

    // --- transaction integrity ---

    public function test_verification_and_status_change_roll_back_together_on_action_failure(): void
    {
        $admin = User::factory()->admin()->create();
        $job = Job::factory()->create(['status' => 'published']);

        // Goi thang Action (bo qua FormRequest) voi note rong de kich hoat
        // ChangeJobStatusAction tu choi trong transaction — xac nhan JobVerification da tao
        // truoc do cung bi rollback (atomicity), khong con lai ban ghi mo côi.
        try {
            app(SaveJobVerificationAction::class)->handle($job, ['result' => 'closed', 'note' => null], $admin);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException) {
            // expected
        }

        $this->assertDatabaseCount('job_verifications', 0);
        $this->assertDatabaseCount('job_status_histories', 0);
        $this->assertSame('published', $job->fresh()->status);
    }
}
