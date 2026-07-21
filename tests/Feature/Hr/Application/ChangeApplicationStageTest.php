<?php

namespace Tests\Feature\Hr\Application;

use App\Models\Application;
use App\Models\ApplicationAppointment;
use App\Models\ApplicationContactAttempt;
use App\Models\ApplicationStatusHistory;
use App\Models\Branch;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChangeApplicationStageTest extends TestCase
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

    private function changeStage(User $actor, Application $application, array $payload)
    {
        return $this->actingAs($actor)->post(route('hr.applications.stage', $application), $payload);
    }

    public function test_guest_is_redirected(): void
    {
        $application = $this->applicationForBranch(Branch::factory()->create()->id);

        $this->post(route('hr.applications.stage', $application), ['to_stage' => 'closed', 'close_reason' => 'other'])
            ->assertRedirect(route('hr.login'));
    }

    public function test_staff_of_other_branch_is_forbidden(): void
    {
        $staff = User::factory()->create();
        $otherBranch = Branch::factory()->create();
        $application = $this->applicationForBranch($otherBranch->id);

        $this->changeStage($staff, $application, ['to_stage' => 'closed', 'close_reason' => 'other'])
            ->assertForbidden();

        $this->assertSame('new', $application->fresh()->stage);
    }

    public function test_admin_can_change_stage_for_any_branch(): void
    {
        $admin = User::factory()->admin()->create();
        $application = $this->applicationForBranch(Branch::factory()->create()->id);
        ApplicationContactAttempt::factory()->create([
            'application_id' => $application->id,
            'workflow_cycle' => $application->workflow_cycle,
        ]);

        $this->changeStage($admin, $application, ['to_stage' => 'contacting'])
            ->assertRedirect(route('hr.applications.index'));

        $this->assertSame('contacting', $application->fresh()->stage);
    }

    public function test_new_to_contacting_requires_contact_attempt_in_current_cycle(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);

        $this->changeStage($staff, $application, ['to_stage' => 'contacting'])
            ->assertSessionHasErrors('to_stage');
        $this->assertSame('new', $application->fresh()->stage);

        ApplicationContactAttempt::factory()->create([
            'application_id' => $application->id,
            'workflow_cycle' => $application->workflow_cycle,
        ]);

        $this->changeStage($staff, $application, ['to_stage' => 'contacting'])
            ->assertRedirect(route('hr.applications.index'));
        $this->assertSame('contacting', $application->fresh()->stage);
    }

    public function test_contacting_to_consulted_requires_consulted_or_interview_agreed_result(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id, ['stage' => 'contacting']);
        ApplicationContactAttempt::factory()->create([
            'application_id' => $application->id,
            'workflow_cycle' => $application->workflow_cycle,
            'result' => 'no_answer',
        ]);

        $this->changeStage($staff, $application, ['to_stage' => 'consulted'])
            ->assertSessionHasErrors('to_stage');

        ApplicationContactAttempt::factory()->create([
            'application_id' => $application->id,
            'workflow_cycle' => $application->workflow_cycle,
            'result' => 'consulted',
        ]);

        $this->changeStage($staff, $application, ['to_stage' => 'consulted'])
            ->assertRedirect(route('hr.applications.index'));
        $this->assertSame('consulted', $application->fresh()->stage);
    }

    public function test_consulted_to_interview_scheduled_requires_scheduled_interview(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id, ['stage' => 'consulted']);

        $this->changeStage($staff, $application, ['to_stage' => 'interview_scheduled'])
            ->assertSessionHasErrors('to_stage');

        ApplicationAppointment::factory()->create([
            'application_id' => $application->id,
            'workflow_cycle' => $application->workflow_cycle,
            'type' => 'interview',
            'status' => 'scheduled',
        ]);

        $this->changeStage($staff, $application, ['to_stage' => 'interview_scheduled'])
            ->assertRedirect(route('hr.applications.index'));
        $this->assertSame('interview_scheduled', $application->fresh()->stage);
    }

    public function test_interview_scheduled_to_interviewed_requires_completed_interview(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id, ['stage' => 'interview_scheduled']);
        ApplicationAppointment::factory()->create([
            'application_id' => $application->id,
            'workflow_cycle' => $application->workflow_cycle,
            'type' => 'interview',
            'status' => 'scheduled',
        ]);

        $this->changeStage($staff, $application, ['to_stage' => 'interviewed'])
            ->assertSessionHasErrors('to_stage');

        ApplicationAppointment::factory()->create([
            'application_id' => $application->id,
            'workflow_cycle' => $application->workflow_cycle,
            'type' => 'interview',
            'status' => 'completed',
        ]);

        $this->changeStage($staff, $application, ['to_stage' => 'interviewed'])
            ->assertRedirect(route('hr.applications.index'));
        $this->assertSame('interviewed', $application->fresh()->stage);
    }

    public function test_interviewed_to_waiting_start_requires_completed_interview_and_expected_start_at(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id, ['stage' => 'interviewed']);
        ApplicationAppointment::factory()->create([
            'application_id' => $application->id,
            'workflow_cycle' => $application->workflow_cycle,
            'type' => 'interview',
            'status' => 'completed',
        ]);

        $this->changeStage($staff, $application, ['to_stage' => 'waiting_start'])
            ->assertSessionHasErrors('expected_start_at');
        $this->assertSame('interviewed', $application->fresh()->stage);

        $this->changeStage($staff, $application, [
            'to_stage' => 'waiting_start',
            'expected_start_at' => now()->addWeek()->toDateString(),
        ])->assertRedirect(route('hr.applications.index'));

        $fresh = $application->fresh();
        $this->assertSame('waiting_start', $fresh->stage);
        $this->assertNotNull($fresh->expected_start_at);
    }

    public function test_waiting_start_to_started_requires_started_at(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id, [
            'stage' => 'waiting_start',
            'expected_start_at' => now()->addDay()->toDateString(),
        ]);

        $this->changeStage($staff, $application, ['to_stage' => 'started'])
            ->assertSessionHasErrors('started_at');

        $this->changeStage($staff, $application, [
            'to_stage' => 'started',
            'started_at' => now()->toDateTimeString(),
        ])->assertRedirect(route('hr.applications.index'));

        $fresh = $application->fresh();
        $this->assertSame('started', $fresh->stage);
        $this->assertNotNull($fresh->started_at);
    }

    public function test_any_active_stage_can_close_with_a_reason(): void
    {
        $staff = User::factory()->create();

        foreach (['new', 'contacting', 'consulted', 'interview_scheduled', 'interviewed', 'waiting_start'] as $stage) {
            $application = $this->applicationForBranch($staff->branch_id, ['stage' => $stage]);

            $this->changeStage($staff, $application, ['to_stage' => 'closed'])
                ->assertSessionHasErrors('close_reason');

            $this->changeStage($staff, $application, ['to_stage' => 'closed', 'close_reason' => 'unreachable'])
                ->assertRedirect(route('hr.applications.index'));

            $fresh = $application->fresh();
            $this->assertSame('closed', $fresh->stage);
            $this->assertSame('unreachable', $fresh->close_reason);
            $this->assertNotNull($fresh->closed_at);
        }
    }

    public function test_duplicate_close_reason_cannot_be_selected_manually(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);

        $this->changeStage($staff, $application, ['to_stage' => 'closed', 'close_reason' => 'duplicate'])
            ->assertSessionHasErrors('close_reason');
        $this->assertSame('new', $application->fresh()->stage);
    }

    public function test_evidence_from_a_different_workflow_cycle_does_not_count(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id, ['workflow_cycle' => 2]);
        ApplicationContactAttempt::factory()->create([
            'application_id' => $application->id,
            'workflow_cycle' => 1,
        ]);

        $this->changeStage($staff, $application, ['to_stage' => 'contacting'])
            ->assertSessionHasErrors('to_stage');
        $this->assertSame('new', $application->fresh()->stage);
    }

    public function test_invalid_transition_pairs_are_rejected(): void
    {
        $staff = User::factory()->create();

        $skipsAhead = $this->applicationForBranch($staff->branch_id, ['stage' => 'new']);
        $this->changeStage($staff, $skipsAhead, ['to_stage' => 'interview_scheduled'])
            ->assertSessionHasErrors('to_stage');

        $fromTerminal = $this->applicationForBranch($staff->branch_id, ['stage' => 'started']);
        $this->changeStage($staff, $fromTerminal, ['to_stage' => 'closed', 'close_reason' => 'other'])
            ->assertSessionHasErrors('to_stage');

        $fromClosed = $this->applicationForBranch($staff->branch_id, ['stage' => 'closed', 'close_reason' => 'other']);
        $this->changeStage($staff, $fromClosed, ['to_stage' => 'contacting'])
            ->assertSessionHasErrors('to_stage');
    }

    public function test_history_record_is_created_and_workflow_cycle_is_unchanged_by_normal_transition(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);
        ApplicationContactAttempt::factory()->create([
            'application_id' => $application->id,
            'workflow_cycle' => $application->workflow_cycle,
        ]);

        $this->changeStage($staff, $application, ['to_stage' => 'contacting']);

        $fresh = $application->fresh();
        $this->assertSame(1, $fresh->workflow_cycle);

        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id,
            'from_stage' => 'new',
            'to_stage' => 'contacting',
            'workflow_cycle' => 1,
            'changed_by' => $staff->id,
            'actor_type' => 'user',
        ]);
        $this->assertSame(1, ApplicationStatusHistory::where('application_id', $application->id)->count());
    }

    public function test_concurrent_stage_change_requests_are_serialized_by_row_lock(): void
    {
        $application = Application::factory()->create();

        config(['database.connections.stage_lock_test_secondary' => config('database.connections.mysql')]);
        $secondConnection = DB::connection('stage_lock_test_secondary');
        $secondConnection->statement('SET SESSION innodb_lock_wait_timeout = 1');

        $blockedByRowLock = false;

        DB::transaction(function () use ($application, $secondConnection, &$blockedByRowLock) {
            Application::whereKey($application->id)->lockForUpdate()->firstOrFail();

            try {
                $secondConnection->transaction(function () use ($secondConnection, $application) {
                    $secondConnection->table('applications')->where('id', $application->id)->lockForUpdate()->first();
                });
            } catch (QueryException $e) {
                $blockedByRowLock = true;
            }
        });

        $this->assertTrue(
            $blockedByRowLock,
            'Connection thu hai phai bi chan boi row lock cua connection dau (chua commit) khi doi giai doan.'
        );

        DB::purge('stage_lock_test_secondary');
    }
}
