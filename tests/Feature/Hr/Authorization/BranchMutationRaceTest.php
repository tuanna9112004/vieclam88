<?php

namespace Tests\Feature\Hr\Authorization;

use App\Actions\Application\ChangeApplicationStageAction;
use App\Actions\Application\DeleteApplicationNoteAction;
use App\Actions\Application\RecordContactAttemptAction;
use App\Actions\Application\SaveApplicationNoteAction;
use App\Actions\Application\ScheduleApplicationAppointmentAction;
use App\Actions\Application\TransferApplicationBranchAction;
use App\Actions\Application\UpdateApplicationAppointmentAction;
use App\Actions\Job\ChangeJobBranchAction;
use App\Actions\Job\CloseJobAction;
use App\Actions\Job\DuplicateJobAction;
use App\Actions\Job\PauseJobAction;
use App\Actions\Job\PublishJobAction;
use App\Actions\Job\SaveJobDraftAction;
use App\Actions\Job\SaveJobVerificationAction;
use App\Enums\JobCloseReason;
use App\Models\Application;
use App\Models\ApplicationAppointment;
use App\Models\ApplicationContactAttempt;
use App\Models\ApplicationNote;
use App\Models\ApplicationStatusHistory;
use App\Models\Branch;
use App\Models\Job;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BranchMutationRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_application_authorization_cannot_mutate_after_branch_transfer(): void
    {
        fake()->unique(true);

        $branchA = Branch::factory()->create(['status' => 'active']);
        $branchB = Branch::factory()->create(['status' => 'active']);
        $staffA = User::factory()->create(['branch_id' => $branchA->id]);
        $superAdmin = User::factory()->superAdmin()->create();

        $contactApplication = $this->applicationForBranch($branchA);
        $stageApplication = $this->applicationForBranch($branchA);
        $closeApplication = $this->applicationForBranch($branchA);
        $reopenApplication = $this->applicationForBranch($branchA, [
            'stage' => 'closed',
            'close_reason' => 'other',
            'closed_at' => now(),
        ]);
        $scheduleApplication = $this->applicationForBranch($branchA);
        $updateAppointmentApplication = $this->applicationForBranch($branchA);
        $noteApplication = $this->applicationForBranch($branchA);

        $appointment = ApplicationAppointment::factory()->create([
            'application_id' => $updateAppointmentApplication->id,
            'status' => 'scheduled',
            'created_by' => $staffA->id,
        ]);
        $note = ApplicationNote::factory()->create([
            'application_id' => $noteApplication->id,
            'user_id' => $staffA->id,
            'content' => 'Noi dung truoc khi chuyen co so',
        ]);

        $this->assertTrue($staffA->can('recordContact', $contactApplication));
        $this->assertTrue($staffA->can('changeStage', $stageApplication));
        $this->assertTrue($staffA->can('changeStage', $closeApplication));
        $this->assertTrue($staffA->can('changeStage', $reopenApplication));
        $this->assertTrue($staffA->can('scheduleAppointment', $scheduleApplication));
        $this->assertTrue($staffA->can('updateAppointment', $updateAppointmentApplication));
        $this->assertTrue($staffA->can('create', [ApplicationNote::class, $noteApplication]));
        $this->assertTrue($staffA->can('update', $note));
        $this->assertTrue($staffA->can('delete', $note));

        $transfer = app(TransferApplicationBranchAction::class);
        foreach ([
            $contactApplication,
            $stageApplication,
            $closeApplication,
            $reopenApplication,
            $scheduleApplication,
            $updateAppointmentApplication,
            $noteApplication,
        ] as $application) {
            $transfer->handle($application, $branchB, $superAdmin, 'Interleaving transfer test');
        }

        $this->assertTrue($staffA->can('recordContact', $contactApplication));
        $this->assertFalse($staffA->can('recordContact', $contactApplication->fresh()));

        $this->assertAuthorizationDenied(fn () => app(RecordContactAttemptAction::class)->handle(
            $contactApplication,
            ['channel' => 'phone', 'result' => 'reached', 'note' => null],
            $staffA
        ));
        $this->assertAuthorizationDenied(fn () => app(ChangeApplicationStageAction::class)->handle(
            $stageApplication,
            ['to_stage' => 'contacting'],
            $staffA
        ));
        $this->assertAuthorizationDenied(fn () => app(ChangeApplicationStageAction::class)->handle(
            $closeApplication,
            ['to_stage' => 'closed', 'close_reason' => 'other'],
            $staffA
        ));
        $this->assertAuthorizationDenied(fn () => app(ChangeApplicationStageAction::class)->handle(
            $reopenApplication,
            ['to_stage' => 'new', 'note' => 'Mo lai'],
            $staffA
        ));
        $this->assertAuthorizationDenied(fn () => app(ScheduleApplicationAppointmentAction::class)->handle(
            $scheduleApplication,
            ['type' => 'callback', 'scheduled_at' => now()->addDay()->toDateTimeString(), 'location_detail' => null],
            $staffA
        ));
        $this->assertAuthorizationDenied(fn () => app(UpdateApplicationAppointmentAction::class)->handle(
            $updateAppointmentApplication,
            $appointment,
            ['status' => 'cancelled', 'outcome' => null, 'note' => null],
            $staffA
        ));
        $this->assertAuthorizationDenied(fn () => app(SaveApplicationNoteAction::class)->handle(
            ['content' => 'Ghi chu moi sau transfer'],
            $noteApplication,
            $staffA
        ));
        $this->assertAuthorizationDenied(fn () => app(SaveApplicationNoteAction::class)->handle(
            ['content' => 'Sua sai sau transfer'],
            $noteApplication,
            $staffA,
            $note
        ));
        $this->assertAuthorizationDenied(fn () => app(DeleteApplicationNoteAction::class)->handle(
            $noteApplication,
            $note,
            $staffA
        ));

        $this->assertDatabaseCount('application_contact_attempts', 0);
        $this->assertDatabaseMissing('application_appointments', ['application_id' => $scheduleApplication->id]);
        $this->assertSame('scheduled', $appointment->fresh()->status);
        $this->assertSame('new', $stageApplication->fresh()->stage);
        $this->assertSame('new', $closeApplication->fresh()->stage);
        $this->assertSame('closed', $reopenApplication->fresh()->stage);
        $this->assertSame(0, ApplicationStatusHistory::count());
        $this->assertSame(1, ApplicationNote::count());
        $this->assertSame('Noi dung truoc khi chuyen co so', $note->fresh()->content);
    }

    public function test_stale_job_authorization_cannot_mutate_or_copy_after_branch_transfer(): void
    {
        fake()->unique(true);

        $branchA = Branch::factory()->create(['status' => 'active']);
        $branchB = Branch::factory()->create(['status' => 'active']);
        $staffA = User::factory()->create(['branch_id' => $branchA->id]);
        $superAdmin = User::factory()->superAdmin()->create();
        $updateJob = Job::factory()->create(['owner_branch_id' => $branchA->id, 'status' => 'draft']);
        $duplicateJob = Job::factory()->create(['owner_branch_id' => $branchA->id, 'status' => 'draft']);
        $publishJob = Job::factory()->create(['owner_branch_id' => $branchA->id, 'status' => 'draft']);
        $pauseJob = Job::factory()->create(['owner_branch_id' => $branchA->id, 'status' => 'published']);
        $closeJob = Job::factory()->create(['owner_branch_id' => $branchA->id, 'status' => 'paused']);
        $verifyJob = Job::factory()->create(['owner_branch_id' => $branchA->id, 'status' => 'paused']);

        $this->assertTrue($staffA->can('update', $updateJob));
        $this->assertTrue($staffA->can('duplicate', $duplicateJob));
        $this->assertTrue($staffA->can('publish', $publishJob));
        $this->assertTrue($staffA->can('pause', $pauseJob));
        $this->assertTrue($staffA->can('close', $closeJob));
        $this->assertTrue($staffA->can('verify', $verifyJob));

        $transfer = app(ChangeJobBranchAction::class);
        foreach ([$updateJob, $duplicateJob, $publishJob, $closeJob, $verifyJob] as $job) {
            $transfer->handle($job, $branchB, $superAdmin, 'Interleaving transfer test');
        }
        // Published Job không được transfer qua domain Action; cập nhật ownership trực tiếp ở
        // đây chỉ mô phỏng snapshot đã đổi giữa FormRequest authorize và Action lock.
        Job::whereKey($pauseJob->id)->update(['owner_branch_id' => $branchB->id]);

        $this->assertTrue($staffA->can('update', $updateJob));
        $this->assertFalse($staffA->can('update', $updateJob->fresh()));

        $originalJobCount = Job::count();
        $this->assertAuthorizationDenied(fn () => app(SaveJobDraftAction::class)->handle([
            'title' => 'Cross branch write',
            'company_id' => $updateJob->company_id,
        ], $staffA, $updateJob));
        $this->assertAuthorizationDenied(
            fn () => app(DuplicateJobAction::class)->handle($duplicateJob, $staffA)
        );
        $this->assertAuthorizationDenied(
            fn () => app(PublishJobAction::class)->handle($publishJob, $staffA)
        );
        $this->assertAuthorizationDenied(
            fn () => app(PauseJobAction::class)->handle($pauseJob, $staffA)
        );
        $this->assertAuthorizationDenied(fn () => app(CloseJobAction::class)->handle(
            $closeJob,
            $staffA,
            JobCloseReason::Other
        ));
        $this->assertAuthorizationDenied(fn () => app(SaveJobVerificationAction::class)->handle(
            $verifyJob,
            ['result' => 'still_open', 'note' => null],
            $staffA
        ));

        $this->assertSame($originalJobCount, Job::count());
        $this->assertNotSame('Cross branch write', $updateJob->fresh()->title);
        $this->assertSame('draft', $publishJob->fresh()->status);
        $this->assertSame('published', $pauseJob->fresh()->status);
        $this->assertSame('paused', $closeJob->fresh()->status);
        $this->assertSame('paused', $verifyJob->fresh()->status);
        $this->assertDatabaseCount('job_status_histories', 0);
        $this->assertDatabaseCount('job_verifications', 0);
    }

    public function test_transfer_reloads_destination_branch_before_changing_ownership(): void
    {
        fake()->unique(true);

        $sourceBranch = Branch::factory()->create(['status' => 'active']);
        $inactiveTarget = Branch::factory()->create(['status' => 'active']);
        $deletedTarget = Branch::factory()->create(['status' => 'active']);
        $staleInactiveTarget = Branch::findOrFail($inactiveTarget->id);
        $staleDeletedTarget = Branch::findOrFail($deletedTarget->id);
        $superAdmin = User::factory()->superAdmin()->create();
        $application = $this->applicationForBranch($sourceBranch);
        $job = Job::factory()->create([
            'owner_branch_id' => $sourceBranch->id,
            'status' => 'draft',
        ]);

        Branch::whereKey($inactiveTarget->id)->update(['status' => 'inactive']);
        Branch::whereKey($deletedTarget->id)->delete();

        $this->assertSame('active', $staleInactiveTarget->status);
        $this->assertNull($staleDeletedTarget->deleted_at);

        $this->assertValidationDenied(
            fn () => app(TransferApplicationBranchAction::class)->handle(
                $application,
                $staleInactiveTarget,
                $superAdmin,
                'Stale inactive destination'
            ),
            'to_branch_id'
        );
        $this->assertValidationDenied(
            fn () => app(ChangeJobBranchAction::class)->handle(
                $job,
                $staleDeletedTarget,
                $superAdmin,
                'Stale deleted destination'
            ),
            'to_branch_id'
        );

        $this->assertSame($sourceBranch->id, $application->fresh()->owner_branch_id);
        $this->assertSame($sourceBranch->id, $job->fresh()->owner_branch_id);
        $this->assertDatabaseCount('application_branch_histories', 0);
        $this->assertDatabaseCount('job_branch_histories', 0);
    }

    public function test_super_admin_remains_unscoped_after_resources_move_branch(): void
    {
        $branchA = Branch::factory()->create(['status' => 'active']);
        $branchB = Branch::factory()->create(['status' => 'active']);
        $superAdmin = User::factory()->superAdmin()->create();
        $application = $this->applicationForBranch($branchA);
        $job = Job::factory()->create(['owner_branch_id' => $branchA->id, 'status' => 'draft']);

        app(TransferApplicationBranchAction::class)
            ->handle($application, $branchB, $superAdmin, 'Super admin transfer');
        app(ChangeJobBranchAction::class)
            ->handle($job, $branchB, $superAdmin, 'Super admin transfer');

        app(RecordContactAttemptAction::class)->handle(
            $application,
            ['channel' => 'phone', 'result' => 'reached', 'note' => null],
            $superAdmin
        );
        app(SaveJobDraftAction::class)->handle([
            'title' => 'Super Admin Updated',
            'company_id' => $job->company_id,
        ], $superAdmin, $job);

        $this->assertSame(1, ApplicationContactAttempt::count());
        $this->assertSame('Super Admin Updated', $job->fresh()->title);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function applicationForBranch(Branch $branch, array $attributes = []): Application
    {
        $job = Job::factory()->create(['owner_branch_id' => $branch->id]);

        return Application::factory()->create([
            'job_id' => $job->id,
            'owner_branch_id' => $branch->id,
            ...$attributes,
        ]);
    }

    private function assertAuthorizationDenied(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected the locked resource to be re-authorized and denied.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }

    private function assertValidationDenied(callable $callback, string $field): void
    {
        try {
            $callback();
            $this->fail('Expected stale destination validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }
}
