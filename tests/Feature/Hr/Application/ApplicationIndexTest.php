<?php

namespace Tests\Feature\Hr\Application;

use App\Models\Application;
use App\Models\ApplicationAppointment;
use App\Models\ApplicationContactAttempt;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_application_index(): void
    {
        $this->get(route('hr.applications.index'))->assertRedirect(route('hr.login'));
    }

    public function test_staff_only_sees_applications_of_own_branch(): void
    {
        $staff = User::factory()->create();
        $ownJob = Job::factory()->create(['owner_branch_id' => $staff->branch_id]);
        $otherBranch = Branch::factory()->create();
        $otherJob = Job::factory()->create(['owner_branch_id' => $otherBranch->id]);

        $ownApplication = Application::factory()->create(['job_id' => $ownJob->id, 'owner_branch_id' => $staff->branch_id]);
        Application::factory()->create(['job_id' => $otherJob->id, 'owner_branch_id' => $otherBranch->id]);

        $response = $this->actingAs($staff)->get(route('hr.applications.index'));

        $response->assertOk();
        $response->assertSee($ownApplication->submitted_phone);
        $this->assertCount(1, $response->viewData('applications'));
    }

    public function test_staff_cannot_override_branch_filter_via_query_string(): void
    {
        $staff = User::factory()->create();
        $otherBranch = Branch::factory()->create();
        $otherJob = Job::factory()->create(['owner_branch_id' => $otherBranch->id]);
        Application::factory()->create(['job_id' => $otherJob->id, 'owner_branch_id' => $otherBranch->id]);

        $response = $this->actingAs($staff)->get(route('hr.applications.index', ['owner_branch_id' => [$otherBranch->id]]));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('applications'));
    }

    public function test_admin_sees_applications_from_all_branches_by_default(): void
    {
        $admin = User::factory()->admin()->create();
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $jobA = Job::factory()->create(['owner_branch_id' => $branchA->id]);
        $jobB = Job::factory()->create(['owner_branch_id' => $branchB->id]);
        Application::factory()->create(['job_id' => $jobA->id, 'owner_branch_id' => $branchA->id]);
        Application::factory()->create(['job_id' => $jobB->id, 'owner_branch_id' => $branchB->id]);

        $response = $this->actingAs($admin)->get(route('hr.applications.index'));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('applications'));
    }

    public function test_admin_can_filter_by_multiple_branches(): void
    {
        $admin = User::factory()->admin()->create();
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $branchC = Branch::factory()->create();
        $jobA = Job::factory()->create(['owner_branch_id' => $branchA->id]);
        $jobB = Job::factory()->create(['owner_branch_id' => $branchB->id]);
        $jobC = Job::factory()->create(['owner_branch_id' => $branchC->id]);
        Application::factory()->create(['job_id' => $jobA->id, 'owner_branch_id' => $branchA->id]);
        Application::factory()->create(['job_id' => $jobB->id, 'owner_branch_id' => $branchB->id]);
        Application::factory()->create(['job_id' => $jobC->id, 'owner_branch_id' => $branchC->id]);

        $response = $this->actingAs($admin)->get(route('hr.applications.index', [
            'owner_branch_id' => [$branchA->id, $branchB->id],
        ]));

        $this->assertCount(2, $response->viewData('applications'));
    }

    public function test_filters_by_candidate_name_and_phone(): void
    {
        $admin = User::factory()->admin()->create();
        $job = Job::factory()->create();
        $targetCandidate = Candidate::factory()->create(['full_name' => 'Nguyễn Văn Đặc Biệt']);
        $target = Application::factory()->create($this->onJob($job, [
            'candidate_id' => $targetCandidate->id,
            'submitted_phone' => '0987001122',
            'submitted_phone_normalized' => '0987001122',
        ]));
        Application::factory()->create($this->onJob($job, ['submitted_phone' => '0911000000', 'submitted_phone_normalized' => '0911000000']));

        $byName = $this->actingAs($admin)->get(route('hr.applications.index', ['q' => 'Đặc Biệt']));
        $this->assertCount(1, $byName->viewData('applications'));
        $this->assertSame($target->id, $byName->viewData('applications')->first()->id);

        $byPhone = $this->actingAs($admin)->get(route('hr.applications.index', ['q' => '0987001122']));
        $this->assertCount(1, $byPhone->viewData('applications'));
        $this->assertSame($target->id, $byPhone->viewData('applications')->first()->id);
    }

    public function test_filters_by_job_and_company(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $job = Job::factory()->create(['company_id' => $company->id]);
        $target = Application::factory()->create(['job_id' => $job->id, 'owner_branch_id' => $job->owner_branch_id]);
        Application::factory()->create();

        $byJob = $this->actingAs($admin)->get(route('hr.applications.index', ['job_id' => $job->id]));
        $this->assertCount(1, $byJob->viewData('applications'));
        $this->assertSame($target->id, $byJob->viewData('applications')->first()->id);

        $byCompany = $this->actingAs($admin)->get(route('hr.applications.index', ['company_id' => $company->id]));
        $this->assertCount(1, $byCompany->viewData('applications'));
        $this->assertSame($target->id, $byCompany->viewData('applications')->first()->id);
    }

    public function test_filters_by_stage_and_date_range(): void
    {
        $admin = User::factory()->admin()->create();
        $job = Job::factory()->create();
        $target = Application::factory()->create($this->onJob($job, ['stage' => 'interviewed', 'created_at' => now()->subDays(2)]));
        Application::factory()->create($this->onJob($job, ['stage' => 'new', 'created_at' => now()->subDays(2)]));
        Application::factory()->create($this->onJob($job, ['stage' => 'interviewed', 'created_at' => now()->subDays(10)]));

        $byStage = $this->actingAs($admin)->get(route('hr.applications.index', ['stage' => 'interviewed']));
        $this->assertCount(2, $byStage->viewData('applications'));

        $byDate = $this->actingAs($admin)->get(route('hr.applications.index', [
            'stage' => 'interviewed',
            'date_from' => now()->subDays(3)->toDateString(),
            'date_to' => now()->toDateString(),
        ]));
        $this->assertCount(1, $byDate->viewData('applications'));
        $this->assertSame($target->id, $byDate->viewData('applications')->first()->id);
    }

    public function test_uncontacted_filter_requires_new_stage_and_no_contact_attempts(): void
    {
        $admin = User::factory()->admin()->create();
        $job = Job::factory()->create();
        $uncontacted = Application::factory()->create($this->onJob($job, ['stage' => 'new']));
        $contactedNew = Application::factory()->create($this->onJob($job, ['stage' => 'new']));
        ApplicationContactAttempt::factory()->create(['application_id' => $contactedNew->id]);
        Application::factory()->create($this->onJob($job, ['stage' => 'contacting']));

        $response = $this->actingAs($admin)->get(route('hr.applications.index', ['uncontacted' => '1']));

        $this->assertCount(1, $response->viewData('applications'));
        $this->assertSame($uncontacted->id, $response->viewData('applications')->first()->id);
    }

    public function test_has_callback_and_has_interview_filters_do_not_duplicate_rows(): void
    {
        $admin = User::factory()->admin()->create();
        $job = Job::factory()->create();
        $withCallback = Application::factory()->create($this->onJob($job));
        ApplicationAppointment::factory()->count(2)->create([
            'application_id' => $withCallback->id,
            'type' => 'callback',
            'status' => 'scheduled',
        ]);
        $withInterview = Application::factory()->create($this->onJob($job));
        ApplicationAppointment::factory()->create([
            'application_id' => $withInterview->id,
            'type' => 'interview',
            'status' => 'scheduled',
        ]);
        Application::factory()->create($this->onJob($job));

        $byCallback = $this->actingAs($admin)->get(route('hr.applications.index', ['has_callback' => '1']));
        $this->assertCount(1, $byCallback->viewData('applications'));
        $this->assertSame($withCallback->id, $byCallback->viewData('applications')->first()->id);

        $byInterview = $this->actingAs($admin)->get(route('hr.applications.index', ['has_interview' => '1']));
        $this->assertCount(1, $byInterview->viewData('applications'));
        $this->assertSame($withInterview->id, $byInterview->viewData('applications')->first()->id);
    }

    public function test_needs_duplicate_review_filter(): void
    {
        $admin = User::factory()->admin()->create();
        $job = Job::factory()->create();
        $flagged = Application::factory()->create($this->onJob($job, ['needs_duplicate_review' => true]));
        Application::factory()->create($this->onJob($job, ['needs_duplicate_review' => false]));

        $response = $this->actingAs($admin)->get(route('hr.applications.index', ['needs_duplicate_review' => '1']));

        $this->assertCount(1, $response->viewData('applications'));
        $this->assertSame($flagged->id, $response->viewData('applications')->first()->id);
    }

    /**
     * Gan Application vao 1 Job/Branch da tao san — tranh moi test tu tao them Job/Company/
     * Branch rieng qua nested factory mac dinh, vi BranchFactory dung pool code nho
     * (fake()->unique()->bothify('BR-###')) de chung cho toan bo tien trinh PHPUnit.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function onJob(Job $job, array $overrides = []): array
    {
        return array_merge(['job_id' => $job->id, 'owner_branch_id' => $job->owner_branch_id], $overrides);
    }
}
