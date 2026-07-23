<?php

namespace Tests\Feature\Hr\Application;

use App\Models\Application;
use App\Models\ApplicationAppointment;
use App\Models\Branch;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentUpdateTest extends TestCase
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

    private function scheduledAppointment(Application $application, array $overrides = []): ApplicationAppointment
    {
        return ApplicationAppointment::factory()->create(array_merge([
            'application_id' => $application->id,
            'status' => 'scheduled',
            'workflow_cycle' => $application->workflow_cycle,
        ], $overrides));
    }

    public function test_guest_is_redirected(): void
    {
        $application = $this->applicationForBranch(Branch::factory()->create()->id);
        $appointment = $this->scheduledAppointment($application, ['type' => 'callback']);

        $this->put(route('hr.applications.appointments.update', [$application, $appointment]), ['status' => 'cancelled'])
            ->assertRedirect(route('hr.login'));
    }

    public function test_staff_of_other_branch_is_forbidden(): void
    {
        $staff = User::factory()->create();
        $otherBranch = Branch::factory()->create();
        $application = $this->applicationForBranch($otherBranch->id);
        $appointment = $this->scheduledAppointment($application, ['type' => 'callback']);

        $this->actingAs($staff)
            ->put(route('hr.applications.appointments.update', [$application, $appointment]), ['status' => 'cancelled'])
            ->assertForbidden();

        $this->assertSame('scheduled', $appointment->fresh()->status);
    }

    public function test_staff_can_mark_callback_completed_without_outcome(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);
        $appointment = $this->scheduledAppointment($application, ['type' => 'callback']);

        $this->actingAs($staff)
            ->put(route('hr.applications.appointments.update', [$application, $appointment]), ['status' => 'completed'])
            ->assertRedirect(route('hr.applications.show', $application));

        $fresh = $appointment->fresh();
        $this->assertSame('completed', $fresh->status);
        $this->assertSame($staff->id, $fresh->completed_by);
        $this->assertNotNull($fresh->completed_at);
    }

    public function test_interview_completed_requires_outcome(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);
        $appointment = $this->scheduledAppointment($application, ['type' => 'interview']);

        $this->actingAs($staff)
            ->put(route('hr.applications.appointments.update', [$application, $appointment]), ['status' => 'completed'])
            ->assertSessionHasErrors('outcome');
        $this->assertSame('scheduled', $appointment->fresh()->status);

        $this->actingAs($staff)
            ->put(route('hr.applications.appointments.update', [$application, $appointment]), [
                'status' => 'completed',
                'outcome' => 'Ứng viên đạt yêu cầu.',
            ])
            ->assertRedirect(route('hr.applications.show', $application));

        $fresh = $appointment->fresh();
        $this->assertSame('completed', $fresh->status);
        $this->assertSame('Ứng viên đạt yêu cầu.', $fresh->outcome);
        $this->assertSame($staff->id, $fresh->completed_by);
    }

    public function test_cancelled_and_no_show_do_not_require_outcome(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);

        $cancelled = $this->scheduledAppointment($application, ['type' => 'interview']);
        $this->actingAs($staff)
            ->put(route('hr.applications.appointments.update', [$application, $cancelled]), ['status' => 'cancelled'])
            ->assertRedirect(route('hr.applications.show', $application));
        $this->assertSame('cancelled', $cancelled->fresh()->status);
        $this->assertNull($cancelled->fresh()->completed_by);

        $noShow = $this->scheduledAppointment($application, ['type' => 'interview']);
        $this->actingAs($staff)
            ->put(route('hr.applications.appointments.update', [$application, $noShow]), ['status' => 'no_show'])
            ->assertRedirect(route('hr.applications.show', $application));
        $this->assertSame('no_show', $noShow->fresh()->status);
    }

    public function test_cannot_update_an_appointment_that_is_not_scheduled(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);
        $appointment = $this->scheduledAppointment($application, ['type' => 'callback', 'status' => 'cancelled']);

        $this->actingAs($staff)
            ->put(route('hr.applications.appointments.update', [$application, $appointment]), ['status' => 'completed'])
            ->assertSessionHasErrors('status');
        $this->assertSame('cancelled', $appointment->fresh()->status);
    }

    public function test_scheduled_at_is_never_modified_by_update(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);
        $originalTime = now()->addDay()->toDateTimeString();
        $appointment = $this->scheduledAppointment($application, ['type' => 'callback', 'scheduled_at' => $originalTime]);

        $this->actingAs($staff)->put(
            route('hr.applications.appointments.update', [$application, $appointment]),
            ['status' => 'completed']
        );

        $this->assertSame($originalTime, $appointment->fresh()->scheduled_at->toDateTimeString());
    }

    public function test_completed_by_is_always_the_authenticated_user_regardless_of_client_input(): void
    {
        $staff = User::factory()->create();
        $otherUser = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);
        $appointment = $this->scheduledAppointment($application, ['type' => 'callback']);

        $this->actingAs($staff)->put(
            route('hr.applications.appointments.update', [$application, $appointment]),
            ['status' => 'completed', 'completed_by' => $otherUser->id]
        );

        $fresh = $appointment->fresh();
        $this->assertSame($staff->id, $fresh->completed_by);
        $this->assertNotSame($otherUser->id, $fresh->completed_by);
    }

    public function test_invalid_status_value_is_rejected(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);
        $appointment = $this->scheduledAppointment($application, ['type' => 'callback']);

        $this->actingAs($staff)
            ->put(route('hr.applications.appointments.update', [$application, $appointment]), ['status' => 'scheduled'])
            ->assertSessionHasErrors('status');

        $this->assertSame('scheduled', $appointment->fresh()->status);
    }

    /**
     * Day la bang chung end-to-end quan trong nhat: chung minh nhan vien co the that su hoan
     * thanh 1 luot phong van qua route/API thuc (khong phai Factory bypass), va bang chung do
     * duoc ChangeApplicationStageAction chap nhan de chuyen interview_scheduled -> interviewed.
     */
    public function test_completing_an_interview_via_the_real_route_unlocks_the_interview_scheduled_to_interviewed_transition(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id, ['stage' => 'interview_scheduled']);
        $appointment = $this->scheduledAppointment($application, ['type' => 'interview']);

        $this->actingAs($staff)->put(
            route('hr.applications.appointments.update', [$application, $appointment]),
            ['status' => 'completed', 'outcome' => 'Đạt yêu cầu.']
        )->assertRedirect(route('hr.applications.show', $application));

        $this->actingAs($staff)
            ->post(route('hr.applications.stage', $application), ['to_stage' => 'interviewed'])
            ->assertRedirect(route('hr.applications.show', $application));

        $this->assertSame('interviewed', $application->fresh()->stage);
    }
}
