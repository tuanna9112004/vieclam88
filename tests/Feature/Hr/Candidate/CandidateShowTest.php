<?php

namespace Tests\Feature\Hr\Candidate;

use App\Models\Application;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\CandidateDuplicateReview;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected(): void
    {
        $candidate = Candidate::factory()->create();

        $this->get(route('hr.candidates.show', $candidate))->assertRedirect(route('hr.login'));
    }

    public function test_staff_with_application_in_own_branch_can_view(): void
    {
        $staff = User::factory()->create();
        $candidate = Candidate::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id]);
        Application::factory()->create([
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'owner_branch_id' => $staff->branch_id,
        ]);

        $this->actingAs($staff)->get(route('hr.candidates.show', $candidate))
            ->assertOk()
            ->assertSee($candidate->full_name);
    }

    public function test_staff_without_any_application_in_family_gets_403_not_a_redirect_or_empty_page(): void
    {
        $staff = User::factory()->create();
        $otherBranch = Branch::factory()->create();
        $candidate = Candidate::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $otherBranch->id]);
        Application::factory()->create([
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'owner_branch_id' => $otherBranch->id,
        ]);

        $this->actingAs($staff)->get(route('hr.candidates.show', $candidate))->assertForbidden();
    }

    public function test_staff_only_sees_applications_from_own_branch_within_the_family(): void
    {
        $staff = User::factory()->create();
        $otherBranch = Branch::factory()->create();
        $candidate = Candidate::factory()->create();

        $ownJob = Job::factory()->create(['owner_branch_id' => $staff->branch_id]);
        $ownApp = Application::factory()->create([
            'candidate_id' => $candidate->id, 'job_id' => $ownJob->id, 'owner_branch_id' => $staff->branch_id,
        ]);

        $otherJob = Job::factory()->create(['owner_branch_id' => $otherBranch->id]);
        Application::factory()->create([
            'candidate_id' => $candidate->id, 'job_id' => $otherJob->id, 'owner_branch_id' => $otherBranch->id,
        ]);

        $response = $this->actingAs($staff)->get(route('hr.candidates.show', $candidate));

        $response->assertOk();
        $applications = $response->viewData('applications');
        $this->assertCount(1, $applications);
        $this->assertSame($ownApp->id, $applications->first()->id);
    }

    public function test_admin_sees_applications_across_all_branches(): void
    {
        $admin = User::factory()->admin()->create();
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $candidate = Candidate::factory()->create();

        Application::factory()->create([
            'candidate_id' => $candidate->id,
            'job_id' => Job::factory()->create(['owner_branch_id' => $branchA->id]),
            'owner_branch_id' => $branchA->id,
        ]);
        Application::factory()->create([
            'candidate_id' => $candidate->id,
            'job_id' => Job::factory()->create(['owner_branch_id' => $branchB->id]),
            'owner_branch_id' => $branchB->id,
        ]);

        $response = $this->actingAs($admin)->get(route('hr.candidates.show', $candidate));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('applications'));
    }

    public function test_direct_url_to_merged_source_redirects_to_root_not_404(): void
    {
        $admin = User::factory()->admin()->create();
        $root = Candidate::factory()->create(['full_name' => 'Ung Vien Goc']);
        $source = Candidate::factory()->create([
            'full_name' => 'Ung Vien Nguon', 'status' => 'merged',
            'merged_into_candidate_id' => $root->id, 'merged_at' => now(), 'merged_by' => $admin->id,
            'merge_reason' => 'trung lap',
        ]);

        $this->actingAs($admin)->get(route('hr.candidates.show', $source))
            ->assertRedirect(route('hr.candidates.show', $root));
    }

    public function test_root_page_lists_merged_sources_and_their_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $root = Candidate::factory()->create(['full_name' => 'Ung Vien Goc']);
        Candidate::factory()->create([
            'full_name' => 'Ung Vien Nguon', 'status' => 'merged',
            'merged_into_candidate_id' => $root->id, 'merged_at' => now(), 'merged_by' => $admin->id,
            'merge_reason' => 'Cung so dien thoai',
        ]);

        $response = $this->actingAs($admin)->get(route('hr.candidates.show', $root));

        $response->assertOk();
        $response->assertSee('Ung Vien Nguon');
        $response->assertSee('Cung so dien thoai');
    }

    public function test_admin_sees_pending_duplicate_reviews_for_the_family_but_staff_does_not(): void
    {
        $staff = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $candidate = Candidate::factory()->create();
        $suspected = Candidate::factory()->create(['full_name' => 'Nghi Ngo Trung']);
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id]);
        $application = Application::factory()->create([
            'candidate_id' => $candidate->id, 'job_id' => $job->id, 'owner_branch_id' => $staff->branch_id,
        ]);
        CandidateDuplicateReview::factory()->create([
            'application_id' => $application->id,
            'candidate_id' => $candidate->id,
            'suspected_candidate_id' => $suspected->id,
            'status' => 'pending',
        ]);

        $adminResponse = $this->actingAs($admin)->get(route('hr.candidates.show', $candidate));
        $adminResponse->assertOk();
        $this->assertCount(1, $adminResponse->viewData('duplicateReviews'));
        $adminResponse->assertSee('Nghi Ngo Trung');

        $staffResponse = $this->actingAs($staff)->get(route('hr.candidates.show', $candidate));
        $staffResponse->assertOk();
        $this->assertCount(0, $staffResponse->viewData('duplicateReviews'));
        $staffResponse->assertDontSee('Nghi Ngo Trung');
    }

    public function test_anonymized_candidate_shows_masked_data_and_warning_banner_not_stale_pii(): void
    {
        $admin = User::factory()->admin()->create();
        $candidate = Candidate::factory()->create([
            'full_name' => '[ĐÃ ẨN DANH]',
            'status' => 'anonymized',
            'anonymized_at' => now(),
            'anonymized_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('hr.candidates.show', $candidate));

        $response->assertOk();
        $response->assertSee('Đã ẩn danh');
        $response->assertDontSee('Gộp ứng viên (Merge)');
        $response->assertDontSee('Ẩn danh ứng viên (Anonymize)');
    }

    public function test_staff_does_not_see_merge_or_anonymize_forms(): void
    {
        $staff = User::factory()->create();
        $candidate = Candidate::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id]);
        Application::factory()->create([
            'candidate_id' => $candidate->id, 'job_id' => $job->id, 'owner_branch_id' => $staff->branch_id,
        ]);

        $response = $this->actingAs($staff)->get(route('hr.candidates.show', $candidate));

        $response->assertOk();
        $response->assertDontSee('Gộp ứng viên (Merge)');
        $response->assertDontSee('Ẩn danh ứng viên (Anonymize)');
    }
}
