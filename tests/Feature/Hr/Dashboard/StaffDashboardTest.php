<?php

namespace Tests\Feature\Hr\Dashboard;

use App\Actions\Dashboard\GetDashboardStatsAction;
use App\Models\Application;
use App\Models\ApplicationAppointment;
use App\Models\ApplicationContactAttempt;
use App\Models\Branch;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_dashboard_is_strictly_isolated_by_branch(): void
    {
        $branchA = Branch::factory()->create(['name' => 'Cơ sở A', 'status' => 'active']);
        $branchB = Branch::factory()->create(['name' => 'Cơ sở B', 'status' => 'active']);

        $staffA = User::factory()->create(['branch_id' => $branchA->id, 'role' => 'staff']);

        $jobA = Job::factory()->create(['owner_branch_id' => $branchA->id, 'status' => 'published']);
        $jobB = Job::factory()->create(['owner_branch_id' => $branchB->id, 'status' => 'published']);

        // Branch A: 2 applications
        Application::factory()->create(['job_id' => $jobA->id, 'owner_branch_id' => $branchA->id, 'stage' => 'new']);
        Application::factory()->create(['job_id' => $jobA->id, 'owner_branch_id' => $branchA->id, 'stage' => 'contacting']);

        // Branch B: 5 applications
        Application::factory()->count(5)->create(['job_id' => $jobB->id, 'owner_branch_id' => $branchB->id, 'stage' => 'new']);

        $action = new GetDashboardStatsAction;
        $statsA = $action->handle($staffA);

        // Staff A sees only Branch A's 2 applications
        $this->assertSame(2, $statsA['new_today']);
        $this->assertSame(1, $statsA['uncontacted']);
        $this->assertSame(1, $statsA['processing']);
    }

    public function test_admin_dashboard_shows_all_branches_or_filtered_branch(): void
    {
        $branchA = Branch::factory()->create(['name' => 'Cơ sở A', 'status' => 'active']);
        $branchB = Branch::factory()->create(['name' => 'Cơ sở B', 'status' => 'active']);

        $admin = User::factory()->admin()->create();

        $jobA = Job::factory()->create(['owner_branch_id' => $branchA->id]);
        $jobB = Job::factory()->create(['owner_branch_id' => $branchB->id]);

        Application::factory()->create(['job_id' => $jobA->id, 'owner_branch_id' => $branchA->id, 'stage' => 'new']);
        Application::factory()->create(['job_id' => $jobB->id, 'owner_branch_id' => $branchB->id, 'stage' => 'new']);

        $action = new GetDashboardStatsAction;

        // All branches
        $statsAll = $action->handle($admin, null);
        $this->assertSame(2, $statsAll['new_today']);

        // Filtered Branch A
        $statsA = $action->handle($admin, $branchA->id);
        $this->assertSame(1, $statsA['new_today']);
    }

    public function test_branch_admin_legacy_dashboard_action_is_scoped_to_own_branch(): void
    {
        fake()->unique(true);

        $ownBranch = Branch::factory()->create(['status' => 'active']);
        $otherBranch = Branch::factory()->create(['status' => 'active']);
        $branchAdmin = User::factory()->branchAdmin()->create(['branch_id' => $ownBranch->id]);
        $ownJob = Job::factory()->create(['owner_branch_id' => $ownBranch->id]);
        $otherJob = Job::factory()->create(['owner_branch_id' => $otherBranch->id]);

        Application::factory()->create([
            'job_id' => $ownJob->id,
            'owner_branch_id' => $ownBranch->id,
        ]);
        Application::factory()->count(2)->create([
            'job_id' => $otherJob->id,
            'owner_branch_id' => $otherBranch->id,
        ]);

        $stats = (new GetDashboardStatsAction)->handle($branchAdmin, $otherBranch->id);

        $this->assertSame(1, $stats['new_today']);
    }

    public function test_dashboard_kpi_cards_count_accurately(): void
    {
        $branch = Branch::factory()->create(['status' => 'active']);
        $staff = User::factory()->create(['branch_id' => $branch->id, 'role' => 'staff']);
        $job = Job::factory()->create(['owner_branch_id' => $branch->id, 'status' => 'draft']);

        // 1. Uncontacted app
        $appUncontacted = Application::factory()->create([
            'job_id' => $job->id,
            'owner_branch_id' => $branch->id,
            'stage' => 'new',
        ]);

        // 2. Contacted app
        $appContacted = Application::factory()->create([
            'job_id' => $job->id,
            'owner_branch_id' => $branch->id,
            'stage' => 'contacting',
        ]);
        ApplicationContactAttempt::factory()->create(['application_id' => $appContacted->id, 'contacted_by' => $staff->id]);

        // 3. App with Callback today
        $appCallback = Application::factory()->create([
            'job_id' => $job->id,
            'owner_branch_id' => $branch->id,
            'stage' => 'consulted',
        ]);
        ApplicationAppointment::factory()->create([
            'application_id' => $appCallback->id,
            'type' => 'callback',
            'status' => 'scheduled',
            'scheduled_at' => now(),
            'created_by' => $staff->id,
        ]);

        // 4. App with Interview today
        $appInterview = Application::factory()->create([
            'job_id' => $job->id,
            'owner_branch_id' => $branch->id,
            'stage' => 'interview_scheduled',
        ]);
        ApplicationAppointment::factory()->create([
            'application_id' => $appInterview->id,
            'type' => 'interview',
            'status' => 'scheduled',
            'scheduled_at' => now(),
            'created_by' => $staff->id,
        ]);

        // 5. App Waiting Start
        Application::factory()->create([
            'job_id' => $job->id,
            'owner_branch_id' => $branch->id,
            'stage' => 'waiting_start',
        ]);

        // 6. App Started
        Application::factory()->create([
            'job_id' => $job->id,
            'owner_branch_id' => $branch->id,
            'stage' => 'started',
        ]);

        // 7. App Closed
        Application::factory()->create([
            'job_id' => $job->id,
            'owner_branch_id' => $branch->id,
            'stage' => 'closed',
            'close_reason' => 'unreachable',
        ]);

        $action = new GetDashboardStatsAction;
        $stats = $action->handle($staff);

        $this->assertSame(7, $stats['new_today']);
        $this->assertSame(1, $stats['uncontacted']);
        $this->assertSame(4, $stats['processing']); // contacting, consulted, interview_scheduled, waiting_start
        $this->assertSame(1, $stats['callbacks_today']);
        $this->assertSame(1, $stats['interviews_today']);
        $this->assertSame(1, $stats['waiting_start']);
        $this->assertSame(1, $stats['started']);
        $this->assertSame(1, $stats['closed']);
        $this->assertSame(1, $stats['jobs_needing_verification']);
    }

    public function test_dashboard_http_endpoint_renders_successfully(): void
    {
        $branch = Branch::factory()->create(['status' => 'active']);
        $staff = User::factory()->create(['branch_id' => $branch->id, 'role' => 'staff']);

        $response = $this->actingAs($staff)->get(route('hr.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard HR');
        $response->assertSee('Hồ sơ mới hôm nay');
        $response->assertSee('Chưa liên hệ');
        $response->assertSee('Đang xử lý');
    }
}
