<?php

namespace Tests\Feature\Hr\Application;

use App\Models\Application;
use App\Models\ApplicationContactAttempt;
use App\Models\Branch;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactAttemptStoreTest extends TestCase
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
            'channel' => 'phone',
            'result' => 'reached',
            'note' => 'Đã gọi, hẹn gọi lại chiều mai.',
        ], $overrides);
    }

    public function test_guest_is_redirected(): void
    {
        $application = $this->applicationForBranch(Branch::factory()->create()->id);

        $this->post(route('hr.applications.contacts.store', $application), $this->validPayload())
            ->assertRedirect(route('hr.login'));
    }

    public function test_staff_of_own_branch_can_record_contact_attempt(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);

        $response = $this->actingAs($staff)->post(
            route('hr.applications.contacts.store', $application),
            $this->validPayload()
        );

        $response->assertRedirect(route('hr.applications.index'));
        $this->assertDatabaseHas('application_contact_attempts', [
            'application_id' => $application->id,
            'contacted_by' => $staff->id,
            'channel' => 'phone',
            'result' => 'reached',
            'workflow_cycle' => $application->workflow_cycle,
        ]);
    }

    public function test_staff_of_other_branch_is_forbidden(): void
    {
        $staff = User::factory()->create();
        $otherBranch = Branch::factory()->create();
        $application = $this->applicationForBranch($otherBranch->id);

        $this->actingAs($staff)
            ->post(route('hr.applications.contacts.store', $application), $this->validPayload())
            ->assertForbidden();

        $this->assertSame(0, ApplicationContactAttempt::count());
    }

    public function test_admin_can_record_contact_attempt_for_any_branch(): void
    {
        $admin = User::factory()->admin()->create();
        $application = $this->applicationForBranch(Branch::factory()->create()->id);

        $this->actingAs($admin)
            ->post(route('hr.applications.contacts.store', $application), $this->validPayload())
            ->assertRedirect(route('hr.applications.index'));

        $this->assertSame(1, ApplicationContactAttempt::count());
    }

    public function test_contacted_by_is_always_the_authenticated_user_regardless_of_client_input(): void
    {
        $staff = User::factory()->create();
        $otherUser = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);

        $this->actingAs($staff)->post(
            route('hr.applications.contacts.store', $application),
            $this->validPayload(['contacted_by' => $otherUser->id])
        );

        $attempt = ApplicationContactAttempt::firstOrFail();
        $this->assertSame($staff->id, $attempt->contacted_by);
        $this->assertNotSame($otherUser->id, $attempt->contacted_by);
    }

    public function test_workflow_cycle_is_always_read_server_side_regardless_of_client_input(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id, ['workflow_cycle' => 2]);

        $this->actingAs($staff)->post(
            route('hr.applications.contacts.store', $application),
            $this->validPayload(['workflow_cycle' => 99])
        );

        $attempt = ApplicationContactAttempt::firstOrFail();
        $this->assertSame(2, $attempt->workflow_cycle);
        $this->assertNotSame(99, $attempt->workflow_cycle);
    }

    public function test_invalid_result_value_is_rejected(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);

        $response = $this->actingAs($staff)->post(
            route('hr.applications.contacts.store', $application),
            $this->validPayload(['result' => 'not_a_real_result'])
        );

        $response->assertSessionHasErrors('result');
        $this->assertSame(0, ApplicationContactAttempt::count());
    }

    public function test_invalid_channel_value_is_rejected(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);

        $response = $this->actingAs($staff)->post(
            route('hr.applications.contacts.store', $application),
            $this->validPayload(['channel' => 'carrier_pigeon'])
        );

        $response->assertSessionHasErrors('channel');
        $this->assertSame(0, ApplicationContactAttempt::count());
    }

    public function test_recording_contact_attempt_never_changes_application_stage(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id, ['stage' => 'new']);

        $this->actingAs($staff)->post(
            route('hr.applications.contacts.store', $application),
            $this->validPayload(['result' => 'consulted'])
        );

        $this->assertSame('new', $application->fresh()->stage);
    }
}
