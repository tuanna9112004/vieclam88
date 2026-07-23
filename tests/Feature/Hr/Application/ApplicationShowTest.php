<?php

namespace Tests\Feature\Hr\Application;

use App\Models\Application;
use App\Models\ApplicationAppointment;
use App\Models\ApplicationBranchHistory;
use App\Models\ApplicationContactAttempt;
use App\Models\ApplicationNote;
use App\Models\ApplicationStatusHistory;
use App\Models\Branch;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationShowTest extends TestCase
{
    use RefreshDatabase;

    private function applicationForBranch(int $branchId, array $overrides = []): Application
    {
        $job = Job::factory()->create(['owner_branch_id' => $branchId]);

        return Application::factory()->create(array_merge([
            'job_id' => $job->id,
            'owner_branch_id' => $branchId,
        ], $overrides));
    }

    public function test_guest_is_redirected(): void
    {
        $application = $this->applicationForBranch(Branch::factory()->create()->id);

        $this->get(route('hr.applications.show', $application))->assertRedirect(route('hr.login'));
    }

    public function test_staff_of_other_branch_is_forbidden(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch(Branch::factory()->create()->id);

        $this->actingAs($staff)->get(route('hr.applications.show', $application))->assertForbidden();
    }

    public function test_staff_of_own_branch_and_admin_can_view(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);

        $this->actingAs($staff)->get(route('hr.applications.show', $application))->assertOk();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('hr.applications.show', $application))->assertOk();
    }

    public function test_timeline_aggregates_all_five_history_sources_without_a_new_table(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);

        ApplicationStatusHistory::factory()->create([
            'application_id' => $application->id,
            'from_stage' => null,
            'to_stage' => 'new',
            'workflow_cycle' => 1,
        ]);
        ApplicationContactAttempt::factory()->create(['application_id' => $application->id, 'workflow_cycle' => 1]);
        ApplicationAppointment::factory()->create(['application_id' => $application->id, 'workflow_cycle' => 1]);
        ApplicationNote::factory()->create(['application_id' => $application->id]);
        ApplicationBranchHistory::factory()->create(['application_id' => $application->id, 'to_branch_id' => $staff->branch_id]);

        $response = $this->actingAs($staff)->get(route('hr.applications.show', $application));

        $response->assertOk();
        $timeline = $response->viewData('timeline');
        $this->assertCount(5, $timeline);
        $this->assertEqualsCanonicalizing(
            ['status_change', 'contact_attempt', 'appointment', 'note', 'branch_transfer'],
            $timeline->pluck('type')->all()
        );
    }

    public function test_timeline_is_sorted_chronologically(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);

        $oldest = ApplicationContactAttempt::factory()->create([
            'application_id' => $application->id,
            'workflow_cycle' => 1,
            'created_at' => now()->subDays(3),
        ]);
        $middle = ApplicationNote::factory()->create([
            'application_id' => $application->id,
            'created_at' => now()->subDays(2),
        ]);
        $newest = ApplicationAppointment::factory()->create([
            'application_id' => $application->id,
            'workflow_cycle' => 1,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($staff)->get(route('hr.applications.show', $application));
        $timeline = $response->viewData('timeline');

        $this->assertSame($oldest->id, $timeline[0]['model']->id);
        $this->assertSame('contact_attempt', $timeline[0]['type']);
        $this->assertSame($middle->id, $timeline[1]['model']->id);
        $this->assertSame($newest->id, $timeline[2]['model']->id);
    }

    public function test_timeline_still_shows_older_workflow_cycle_entries_after_reopen(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id, [
            'stage' => 'closed',
            'close_reason' => 'unreachable',
            'workflow_cycle' => 1,
        ]);
        $application->job->update(['status' => 'published']);

        $oldCycleAttempt = ApplicationContactAttempt::factory()->create([
            'application_id' => $application->id,
            'workflow_cycle' => 1,
        ]);

        $this->actingAs($staff)->post(route('hr.applications.stage', $application), [
            'to_stage' => 'new',
            'note' => 'Ứng viên liên hệ lại.',
        ])->assertRedirect(route('hr.applications.show', $application));

        $newCycleAttempt = ApplicationContactAttempt::factory()->create([
            'application_id' => $application->id,
            'workflow_cycle' => $application->fresh()->workflow_cycle,
        ]);

        $response = $this->actingAs($staff)->get(route('hr.applications.show', $application));
        $timeline = $response->viewData('timeline');

        $ids = $timeline->pluck('model.id')->all();
        $this->assertContains($oldCycleAttempt->id, $ids);
        $this->assertContains($newCycleAttempt->id, $ids);

        $cycles = $timeline->where('type', 'contact_attempt')->pluck('workflow_cycle')->all();
        $this->assertContains(1, $cycles);
        $this->assertContains(2, $cycles);
    }

    public function test_soft_deleted_note_does_not_appear_in_timeline(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);
        $note = ApplicationNote::factory()->create(['application_id' => $application->id]);
        $note->delete();

        $response = $this->actingAs($staff)->get(route('hr.applications.show', $application));
        $timeline = $response->viewData('timeline');

        $this->assertCount(0, $timeline->where('type', 'note'));
    }
}
