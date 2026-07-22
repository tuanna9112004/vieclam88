<?php

namespace Tests\Feature\Hr\Dashboard;

use App\Actions\Application\TransferApplicationBranchAction;
use App\Actions\Candidate\MergeCandidateAction;
use App\Actions\Dashboard\GetAdminDashboardStatsAction;
use App\Models\Application;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_conversion_rate_is_calculated_correctly(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create(['status' => 'active']);
        $job = Job::factory()->create(['owner_branch_id' => $branch->id]);

        // 4 applications in total: 1 started, 1 new, 1 contacting, 1 closed
        Application::factory()->create(['job_id' => $job->id, 'owner_branch_id' => $branch->id, 'stage' => 'started']);
        Application::factory()->create(['job_id' => $job->id, 'owner_branch_id' => $branch->id, 'stage' => 'new']);
        Application::factory()->create(['job_id' => $job->id, 'owner_branch_id' => $branch->id, 'stage' => 'contacting']);
        Application::factory()->create(['job_id' => $job->id, 'owner_branch_id' => $branch->id, 'stage' => 'closed']);

        $action = new GetAdminDashboardStatsAction();
        $stats = $action->handle($admin, []);

        $this->assertSame(4, $stats['total_applications']);
        $this->assertSame(1, $stats['started']);
        $this->assertSame(25.0, $stats['conversion_rate']); // 1 / 4 * 100 = 25%
    }

    public function test_admin_dashboard_filters_by_company_job_and_date_range(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create(['status' => 'active']);

        $companyA = Company::factory()->create(['name' => 'Công Ty A']);
        $companyB = Company::factory()->create(['name' => 'Công Ty B']);

        $jobA1 = Job::factory()->create(['company_id' => $companyA->id, 'owner_branch_id' => $branch->id]);
        $jobA2 = Job::factory()->create(['company_id' => $companyA->id, 'owner_branch_id' => $branch->id]);
        $jobB = Job::factory()->create(['company_id' => $companyB->id, 'owner_branch_id' => $branch->id]);

        // App A1 created yesterday
        Application::factory()->create([
            'job_id' => $jobA1->id,
            'owner_branch_id' => $branch->id,
            'created_at' => now()->subDay(),
        ]);

        // App A2 created today
        Application::factory()->create([
            'job_id' => $jobA2->id,
            'owner_branch_id' => $branch->id,
            'created_at' => now(),
        ]);

        // App B created today
        Application::factory()->create([
            'job_id' => $jobB->id,
            'owner_branch_id' => $branch->id,
            'created_at' => now(),
        ]);

        $action = new GetAdminDashboardStatsAction();

        // 1. Filter by Company A
        $statsCompanyA = $action->handle($admin, ['company_id' => $companyA->id]);
        $this->assertSame(2, $statsCompanyA['total_applications']);

        // 2. Filter by Job A1
        $statsJobA1 = $action->handle($admin, ['job_id' => $jobA1->id]);
        $this->assertSame(1, $statsJobA1['total_applications']);

        // 3. Filter by Date Range (today only)
        $todayStr = now()->toDateString();
        $statsToday = $action->handle($admin, ['date_from' => $todayStr, 'date_to' => $todayStr]);
        $this->assertSame(2, $statsToday['total_applications']);
    }

    public function test_counts_remain_accurate_after_transfer_merge_and_duplicate_actions(): void
    {
        $admin = User::factory()->admin()->create();
        $branch1 = Branch::factory()->create(['name' => 'Branch 1', 'status' => 'active']);
        $branch2 = Branch::factory()->create(['name' => 'Branch 2', 'status' => 'active']);

        $job = Job::factory()->create(['owner_branch_id' => $branch1->id, 'status' => 'published']);

        $sourceCand = Candidate::factory()->create(['status' => 'active']);
        $targetCand = Candidate::factory()->create(['status' => 'active']);

        // Both candidates applied for $job
        $app1 = Application::factory()->create([
            'candidate_id' => $sourceCand->id,
            'job_id' => $job->id,
            'owner_branch_id' => $branch1->id,
            'stage' => 'contacting',
            'workflow_cycle' => 1,
        ]);

        $app2 = Application::factory()->create([
            'candidate_id' => $targetCand->id,
            'job_id' => $job->id,
            'owner_branch_id' => $branch1->id,
            'stage' => 'started',
            'workflow_cycle' => 1,
        ]);

        // 1. Perform Branch Transfer on App1 (from Branch 1 to Branch 2)
        $transferAction = new TransferApplicationBranchAction();
        $transferAction->handle($app1, $branch2, $admin, 'Chuyển cơ sở thử nghiệm');

        // 2. Perform Candidate Merge (Source -> Target)
        $mergeAction = new MergeCandidateAction();
        $mergeAction->handle($sourceCand, $targetCand, $admin, 'Gộp thử nghiệm', $app2->id);

        $statsAction = new GetAdminDashboardStatsAction();

        // System-wide total applications is still 2
        $statsTotal = $statsAction->handle($admin, []);
        $this->assertSame(2, $statsTotal['total_applications']);
        $this->assertSame(1, $statsTotal['started']); // App2
        $this->assertSame(1, $statsTotal['closed']);  // App1 closed due to duplicate merge conflict

        // Branch 1 has App2
        $statsBranch1 = $statsAction->handle($admin, ['owner_branch_id' => $branch1->id]);
        $this->assertSame(1, $statsBranch1['total_applications']);

        // Branch 2 has App1
        $statsBranch2 = $statsAction->handle($admin, ['owner_branch_id' => $branch2->id]);
        $this->assertSame(1, $statsBranch2['total_applications']);
    }
}
