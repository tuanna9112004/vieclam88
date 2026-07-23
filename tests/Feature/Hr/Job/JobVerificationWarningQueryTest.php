<?php

namespace Tests\Feature\Hr\Job;

use App\Actions\Dashboard\GetAdminDashboardStatsAction;
use App\Actions\Dashboard\GetDashboardStatsAction;
use App\Models\Branch;
use App\Models\Job;
use App\Models\Setting;
use App\Models\User;
use App\Support\JobVerificationWarning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobVerificationWarningQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_stale_scope_matches_boundary_6_7_8_days(): void
    {
        $fresh6 = Job::factory()->create(['status' => 'published', 'last_verified_at' => now()->subDays(6)]);
        $boundary7 = Job::factory()->create(['status' => 'published', 'last_verified_at' => now()->subDays(7)]);
        $stale8 = Job::factory()->create(['status' => 'published', 'last_verified_at' => now()->subDays(8)]);

        $ids = Job::query()->publishedStale(7)->pluck('id')->all();

        $this->assertNotContains($fresh6->id, $ids);
        $this->assertNotContains($boundary7->id, $ids);
        $this->assertContains($stale8->id, $ids);
    }

    public function test_published_stale_scope_matches_boundary_13_14_15_days(): void
    {
        $warning13 = Job::factory()->create(['status' => 'published', 'last_verified_at' => now()->subDays(13)]);
        $boundary14 = Job::factory()->create(['status' => 'published', 'last_verified_at' => now()->subDays(14)]);
        $critical15 = Job::factory()->create(['status' => 'published', 'last_verified_at' => now()->subDays(15)]);

        $ids = Job::query()->publishedStale(14)->pluck('id')->all();

        $this->assertNotContains($warning13->id, $ids);
        $this->assertNotContains($boundary14->id, $ids);
        $this->assertContains($critical15->id, $ids);
    }

    public function test_published_stale_scope_falls_back_to_published_at_when_never_verified(): void
    {
        $neverVerifiedFresh = Job::factory()->create([
            'status' => 'published', 'last_verified_at' => null, 'published_at' => now()->subDays(6),
        ]);
        $neverVerifiedStale = Job::factory()->create([
            'status' => 'published', 'last_verified_at' => null, 'published_at' => now()->subDays(8),
        ]);

        $ids = Job::query()->publishedStale(7)->pluck('id')->all();

        $this->assertNotContains($neverVerifiedFresh->id, $ids);
        $this->assertContains($neverVerifiedStale->id, $ids);
    }

    public function test_published_stale_scope_excludes_draft_paused_closed_regardless_of_age(): void
    {
        $draft = Job::factory()->create(['status' => 'draft', 'last_verified_at' => now()->subDays(365)]);
        $paused = Job::factory()->create(['status' => 'paused', 'last_verified_at' => now()->subDays(365)]);
        $closed = Job::factory()->create(['status' => 'closed', 'last_verified_at' => now()->subDays(365)]);

        $ids = Job::query()->publishedStale(7)->pluck('id')->all();

        $this->assertNotContains($draft->id, $ids);
        $this->assertNotContains($paused->id, $ids);
        $this->assertNotContains($closed->id, $ids);
    }

    public function test_thresholds_reads_from_settings_when_present(): void
    {
        Setting::query()->updateOrCreate(['key' => 'job_verification_warning_days'], ['value' => '5', 'type' => 'integer']);
        Setting::query()->updateOrCreate(['key' => 'job_auto_pause_days'], ['value' => '10', 'type' => 'integer']);

        $thresholds = JobVerificationWarning::thresholds();

        $this->assertSame(5, $thresholds['warning']);
        $this->assertSame(10, $thresholds['critical']);
    }

    public function test_thresholds_falls_back_to_documented_defaults_when_settings_missing(): void
    {
        // Khong seed Setting nao — phai fallback dung mac dinh da cong bo (muc 1.3, ADR-042).
        $thresholds = JobVerificationWarning::thresholds();

        $this->assertSame(7, $thresholds['warning']);
        $this->assertSame(14, $thresholds['critical']);
    }

    public function test_admin_dashboard_jobs_needing_verification_uses_warning_threshold_not_null_check(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create(['status' => 'active']);

        Setting::query()->updateOrCreate(['key' => 'job_verification_warning_days'], ['value' => '7', 'type' => 'integer']);

        $draft = Job::factory()->create(['status' => 'draft', 'owner_branch_id' => $branch->id]);
        $freshPublished = Job::factory()->create([
            'status' => 'published', 'owner_branch_id' => $branch->id, 'last_verified_at' => now()->subDays(6),
        ]);
        $stalePublished = Job::factory()->create([
            'status' => 'published', 'owner_branch_id' => $branch->id, 'last_verified_at' => now()->subDays(8),
        ]);

        $stats = (new GetAdminDashboardStatsAction())->handle($admin, []);

        // Draft (1) + published qua han canh bao (1) = 2. Published con moi (fresh6) KHONG duoc dem
        // (bug cu: dem moi Job co last_verified_at IS NULL bat ke nguong ngay nao).
        $this->assertSame(2, $stats['jobs_needing_verification']);
        $this->assertNotNull($draft);
        $this->assertNotNull($freshPublished);
        $this->assertNotNull($stalePublished);
    }

    public function test_staff_dashboard_jobs_needing_verification_uses_warning_threshold_not_null_check(): void
    {
        $branch = Branch::factory()->create(['status' => 'active']);
        $staff = User::factory()->create(['branch_id' => $branch->id, 'role' => 'staff']);

        Job::factory()->create(['status' => 'draft', 'owner_branch_id' => $branch->id]);
        Job::factory()->create([
            'status' => 'published', 'owner_branch_id' => $branch->id, 'last_verified_at' => now()->subDays(6),
        ]);
        Job::factory()->create([
            'status' => 'published', 'owner_branch_id' => $branch->id, 'last_verified_at' => now()->subDays(8),
        ]);

        $stats = (new GetDashboardStatsAction())->handle($staff);

        $this->assertSame(2, $stats['jobs_needing_verification']);
    }

    public function test_jobs_needing_verification_ignores_applications_in_other_branches(): void
    {
        $admin = User::factory()->admin()->create();
        $branch1 = Branch::factory()->create(['status' => 'active']);
        $branch2 = Branch::factory()->create(['status' => 'active']);

        Job::factory()->create([
            'status' => 'published', 'owner_branch_id' => $branch1->id, 'last_verified_at' => now()->subDays(8),
        ]);
        $jobBranch2 = Job::factory()->create([
            'status' => 'published', 'owner_branch_id' => $branch2->id, 'last_verified_at' => now()->subDays(8),
        ]);

        $stats = (new GetAdminDashboardStatsAction())->handle($admin, ['owner_branch_id' => $branch1->id]);

        $this->assertSame(1, $stats['jobs_needing_verification']);
        $this->assertNotNull($jobBranch2);
    }

    public function test_job_list_shows_warning_and_critical_badges_using_the_same_predicate(): void
    {
        $admin = User::factory()->admin()->create();
        Setting::query()->updateOrCreate(['key' => 'job_verification_warning_days'], ['value' => '7', 'type' => 'integer']);
        Setting::query()->updateOrCreate(['key' => 'job_auto_pause_days'], ['value' => '14', 'type' => 'integer']);

        Job::factory()->create(['status' => 'published', 'last_verified_at' => now()->subDays(6), 'title' => 'Job Con Moi']);
        Job::factory()->create(['status' => 'published', 'last_verified_at' => now()->subDays(8), 'title' => 'Job Can Xac Minh']);
        Job::factory()->create(['status' => 'published', 'last_verified_at' => now()->subDays(15), 'title' => 'Job Qua Han']);

        $response = $this->actingAs($admin)->get(route('hr.jobs.index'));

        $response->assertOk();
        $response->assertSee('Cần xác minh lại');
        $response->assertSee('Quá hạn xác minh');
    }
}
