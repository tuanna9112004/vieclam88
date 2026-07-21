<?php

namespace Tests\Feature\Hr\Application;

use App\Models\Application;
use App\Models\ApplicationAppointment;
use App\Models\Branch;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentStoreTest extends TestCase
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

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'callback',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'location_detail' => null,
        ], $overrides);
    }

    public function test_guest_is_redirected(): void
    {
        $application = $this->applicationForBranch(Branch::factory()->create()->id);

        $this->post(route('hr.applications.appointments.store', $application), $this->validPayload())
            ->assertRedirect(route('hr.login'));
    }

    public function test_staff_of_own_branch_can_schedule_appointment(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);
        $scheduledAt = now()->addDay()->toDateTimeString();

        $response = $this->actingAs($staff)->post(
            route('hr.applications.appointments.store', $application),
            $this->validPayload(['scheduled_at' => $scheduledAt])
        );

        $response->assertRedirect(route('hr.applications.index'));
        $this->assertDatabaseHas('application_appointments', [
            'application_id' => $application->id,
            'type' => 'callback',
            'status' => 'scheduled',
            'created_by' => $staff->id,
            'workflow_cycle' => $application->workflow_cycle,
        ]);
    }

    public function test_staff_of_other_branch_is_forbidden(): void
    {
        $staff = User::factory()->create();
        $otherBranch = Branch::factory()->create();
        $application = $this->applicationForBranch($otherBranch->id);

        $this->actingAs($staff)
            ->post(route('hr.applications.appointments.store', $application), $this->validPayload())
            ->assertForbidden();

        $this->assertSame(0, ApplicationAppointment::count());
    }

    public function test_admin_can_schedule_appointment_for_any_branch(): void
    {
        $admin = User::factory()->admin()->create();
        $application = $this->applicationForBranch(Branch::factory()->create()->id);

        $this->actingAs($admin)
            ->post(route('hr.applications.appointments.store', $application), $this->validPayload(['type' => 'interview']))
            ->assertRedirect(route('hr.applications.index'));

        $this->assertSame(1, ApplicationAppointment::count());
    }

    public function test_created_by_is_always_the_authenticated_user_regardless_of_client_input(): void
    {
        $staff = User::factory()->create();
        $otherUser = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);

        $this->actingAs($staff)->post(
            route('hr.applications.appointments.store', $application),
            $this->validPayload(['created_by' => $otherUser->id])
        );

        $appointment = ApplicationAppointment::firstOrFail();
        $this->assertSame($staff->id, $appointment->created_by);
        $this->assertNotSame($otherUser->id, $appointment->created_by);
    }

    public function test_workflow_cycle_is_always_read_server_side_regardless_of_client_input(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id, ['workflow_cycle' => 2]);

        $this->actingAs($staff)->post(
            route('hr.applications.appointments.store', $application),
            $this->validPayload(['workflow_cycle' => 99])
        );

        $appointment = ApplicationAppointment::firstOrFail();
        $this->assertSame(2, $appointment->workflow_cycle);
        $this->assertNotSame(99, $appointment->workflow_cycle);
    }

    public function test_invalid_type_is_rejected(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);

        $response = $this->actingAs($staff)->post(
            route('hr.applications.appointments.store', $application),
            $this->validPayload(['type' => 'lunch'])
        );

        $response->assertSessionHasErrors('type');
        $this->assertSame(0, ApplicationAppointment::count());
    }

    public function test_missing_scheduled_at_is_rejected(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);

        $response = $this->actingAs($staff)->post(
            route('hr.applications.appointments.store', $application),
            $this->validPayload(['scheduled_at' => null])
        );

        $response->assertSessionHasErrors('scheduled_at');
        $this->assertSame(0, ApplicationAppointment::count());
    }

    public function test_rescheduling_cancels_old_appointment_without_touching_its_scheduled_at_and_creates_a_new_one(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);
        $originalTime = now()->addDay()->toDateTimeString();

        $this->actingAs($staff)->post(
            route('hr.applications.appointments.store', $application),
            $this->validPayload(['type' => 'callback', 'scheduled_at' => $originalTime])
        );
        $original = ApplicationAppointment::firstOrFail();

        $newTime = now()->addDays(3)->toDateTimeString();
        $this->actingAs($staff)->post(
            route('hr.applications.appointments.store', $application),
            $this->validPayload(['type' => 'callback', 'scheduled_at' => $newTime])
        );

        $this->assertSame(2, ApplicationAppointment::count());

        $original->refresh();
        $this->assertSame('cancelled', $original->status);
        $this->assertSame($originalTime, $original->scheduled_at->toDateTimeString());

        $newest = ApplicationAppointment::where('id', '!=', $original->id)->firstOrFail();
        $this->assertSame('scheduled', $newest->status);
        $this->assertSame($newTime, $newest->scheduled_at->toDateTimeString());
    }

    public function test_rescheduling_one_type_does_not_cancel_a_scheduled_appointment_of_the_other_type(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);

        $this->actingAs($staff)->post(
            route('hr.applications.appointments.store', $application),
            $this->validPayload(['type' => 'callback'])
        );
        $callback = ApplicationAppointment::firstOrFail();

        $this->actingAs($staff)->post(
            route('hr.applications.appointments.store', $application),
            $this->validPayload(['type' => 'interview'])
        );

        $this->assertSame('scheduled', $callback->fresh()->status);
        $this->assertSame(2, ApplicationAppointment::count());
    }

    public function test_rescheduling_does_not_touch_a_scheduled_appointment_from_an_older_workflow_cycle(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id, ['workflow_cycle' => 2]);
        $staleAppointment = ApplicationAppointment::factory()->create([
            'application_id' => $application->id,
            'type' => 'callback',
            'status' => 'scheduled',
            'workflow_cycle' => 1,
        ]);

        $this->actingAs($staff)->post(
            route('hr.applications.appointments.store', $application),
            $this->validPayload(['type' => 'callback'])
        );

        $this->assertSame('scheduled', $staleAppointment->fresh()->status);
        $this->assertSame(2, ApplicationAppointment::count());
    }
}
