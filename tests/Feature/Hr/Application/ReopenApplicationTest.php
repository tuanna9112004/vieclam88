<?php

namespace Tests\Feature\Hr\Application;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReopenApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function closedApplicationForBranch(int $branchId, array $jobOverrides = [], array $applicationOverrides = []): Application
    {
        $job = Job::factory()->create(array_merge(['owner_branch_id' => $branchId], $jobOverrides));

        return Application::factory()->create(array_merge([
            'job_id' => $job->id,
            'owner_branch_id' => $branchId,
            'stage' => 'closed',
            'close_reason' => 'unreachable',
            'closed_at' => now()->subDay(),
            'expected_start_at' => now()->addWeek()->toDateString(),
            'workflow_cycle' => 1,
        ], $applicationOverrides));
    }

    private function reopen(User $actor, Application $application, array $payload = [])
    {
        return $this->actingAs($actor)->post(
            route('hr.applications.stage', $application),
            array_merge(['to_stage' => 'new', 'note' => 'Ứng viên chủ động liên hệ lại.'], $payload)
        );
    }

    public function test_reopen_requires_a_reason(): void
    {
        $staff = User::factory()->create();
        $application = $this->closedApplicationForBranch($staff->branch_id, ['status' => 'published']);

        $this->reopen($staff, $application, ['note' => ''])->assertSessionHasErrors('note');
        $this->assertSame('closed', $application->fresh()->stage);
    }

    public function test_reopen_is_rejected_when_close_reason_is_duplicate(): void
    {
        $staff = User::factory()->create();
        $application = $this->closedApplicationForBranch(
            $staff->branch_id,
            ['status' => 'published'],
            ['close_reason' => 'duplicate']
        );

        $this->reopen($staff, $application)->assertSessionHasErrors('to_stage');
        $this->assertSame('closed', $application->fresh()->stage);
    }

    public function test_reopen_is_rejected_when_application_is_not_closed(): void
    {
        $staff = User::factory()->create();
        $application = $this->closedApplicationForBranch($staff->branch_id, ['status' => 'published'], ['stage' => 'new']);

        $this->reopen($staff, $application)->assertSessionHasErrors('to_stage');
    }

    public function test_reopen_is_rejected_when_candidate_is_anonymized(): void
    {
        $staff = User::factory()->create();
        $candidate = Candidate::factory()->create(['status' => 'anonymized']);
        $application = $this->closedApplicationForBranch(
            $staff->branch_id,
            ['status' => 'published'],
            ['candidate_id' => $candidate->id]
        );

        $this->reopen($staff, $application)->assertSessionHasErrors('to_stage');
        $this->assertSame('closed', $application->fresh()->stage);
    }

    public function test_reopen_is_rejected_when_candidate_is_merged(): void
    {
        $staff = User::factory()->create();
        $root = Candidate::factory()->create();
        $candidate = Candidate::factory()->create(['status' => 'merged', 'merged_into_candidate_id' => $root->id]);
        $application = $this->closedApplicationForBranch(
            $staff->branch_id,
            ['status' => 'published'],
            ['candidate_id' => $candidate->id]
        );

        $this->reopen($staff, $application)->assertSessionHasErrors('to_stage');
    }

    public function test_reopen_is_rejected_when_candidate_is_soft_deleted(): void
    {
        $staff = User::factory()->create();
        $candidate = Candidate::factory()->create();
        $candidate->delete();
        $application = $this->closedApplicationForBranch(
            $staff->branch_id,
            ['status' => 'published'],
            ['candidate_id' => $candidate->id]
        );

        $this->reopen($staff, $application)->assertSessionHasErrors('to_stage');
    }

    public function test_reopen_is_rejected_when_job_is_soft_deleted(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id, 'status' => 'published']);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'owner_branch_id' => $staff->branch_id,
            'stage' => 'closed',
            'close_reason' => 'unreachable',
        ]);
        $job->delete();

        $this->reopen($staff, $application)->assertSessionHasErrors('to_stage');
    }

    public function test_staff_cannot_reopen_when_job_no_longer_open_but_admin_can(): void
    {
        $staff = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $applicationForStaff = $this->closedApplicationForBranch($staff->branch_id, ['status' => 'paused']);
        $this->reopen($staff, $applicationForStaff)->assertSessionHasErrors('to_stage');
        $this->assertSame('closed', $applicationForStaff->fresh()->stage);

        $applicationForAdmin = $this->closedApplicationForBranch(Branch::factory()->create()->id, ['status' => 'closed']);
        $this->reopen($admin, $applicationForAdmin)->assertRedirect(route('hr.applications.index'));
        $this->assertSame('new', $applicationForAdmin->fresh()->stage);
    }

    public function test_staff_can_reopen_when_job_still_open_and_not_expired(): void
    {
        $staff = User::factory()->create();
        $application = $this->closedApplicationForBranch($staff->branch_id, ['status' => 'published', 'expires_at' => null]);

        $this->reopen($staff, $application)->assertRedirect(route('hr.applications.index'));
        $this->assertSame('new', $application->fresh()->stage);
    }

    public function test_reopen_is_rejected_when_job_is_expired_for_staff(): void
    {
        $staff = User::factory()->create();
        $application = $this->closedApplicationForBranch($staff->branch_id, [
            'status' => 'published',
            'expires_at' => now()->subDay(),
        ]);

        $this->reopen($staff, $application)->assertSessionHasErrors('to_stage');
        $this->assertSame('closed', $application->fresh()->stage);
    }

    public function test_reopen_resets_derived_fields_and_increments_workflow_cycle(): void
    {
        $staff = User::factory()->create();
        $application = $this->closedApplicationForBranch(
            $staff->branch_id,
            ['status' => 'published', 'expires_at' => null],
            ['workflow_cycle' => 1]
        );

        $this->reopen($staff, $application, ['note' => 'Ứng viên xin xem xét lại.'])
            ->assertRedirect(route('hr.applications.index'));

        $fresh = $application->fresh();
        $this->assertSame('new', $fresh->stage);
        $this->assertNull($fresh->close_reason);
        $this->assertNull($fresh->closed_at);
        $this->assertNull($fresh->expected_start_at);
        $this->assertSame(2, $fresh->workflow_cycle);
        $this->assertNotNull($fresh->workflow_cycle_started_at);
        $this->assertNotNull($fresh->reopened_at);
        $this->assertSame($staff->id, $fresh->reopened_by);

        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id,
            'from_stage' => 'closed',
            'to_stage' => 'new',
            'workflow_cycle' => 2,
            'changed_by' => $staff->id,
            'actor_type' => 'user',
            'note' => 'Ứng viên xin xem xét lại.',
        ]);
        $this->assertSame(1, ApplicationStatusHistory::where('application_id', $application->id)->count());
    }
}
