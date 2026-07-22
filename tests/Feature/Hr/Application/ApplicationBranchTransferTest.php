<?php

namespace Tests\Feature\Hr\Application;

use App\Models\Application;
use App\Models\ApplicationAppointment;
use App\Models\ApplicationContactAttempt;
use App\Models\ApplicationNote;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationBranchTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_transfer_application_branch(): void
    {
        $branchOld = Branch::factory()->create(['status' => 'active']);
        $branchNew = Branch::factory()->create(['status' => 'active']);

        $staff = User::factory()->create(['branch_id' => $branchOld->id, 'role' => 'staff']);
        $admin = User::factory()->admin()->create();

        $application = Application::factory()->create([
            'owner_branch_id' => $branchOld->id,
            'stage' => 'contacting',
        ]);

        // Staff of current branch gets 403 Forbidden
        $this->actingAs($staff)
            ->post(route('hr.applications.transfer-branch', $application), [
                'to_branch_id' => $branchNew->id,
                'reason' => 'Bàn giao lại cơ sở quản lý',
            ])
            ->assertForbidden();

        $this->assertSame($branchOld->id, $application->fresh()->owner_branch_id);

        // Admin gets redirected successfully
        $this->actingAs($admin)
            ->post(route('hr.applications.transfer-branch', $application), [
                'to_branch_id' => $branchNew->id,
                'reason' => 'Bàn giao lại cơ sở quản lý',
            ])
            ->assertRedirect(route('hr.applications.show', $application))
            ->assertSessionHas('status', 'Đã chuyển cơ sở phụ trách hồ sơ.');

        $this->assertSame($branchNew->id, $application->fresh()->owner_branch_id);
    }

    public function test_transfer_validation_rules(): void
    {
        $admin = User::factory()->admin()->create();
        $branchOld = Branch::factory()->create(['status' => 'active']);
        $branchInactive = Branch::factory()->create(['status' => 'inactive']);
        $branchTrashed = Branch::factory()->create(['status' => 'active']);
        $branchTrashed->delete();

        $application = Application::factory()->create([
            'owner_branch_id' => $branchOld->id,
        ]);

        // Reason is required
        $this->actingAs($admin)
            ->post(route('hr.applications.transfer-branch', $application), [
                'to_branch_id' => Branch::factory()->create(['status' => 'active'])->id,
                'reason' => '   ',
            ])
            ->assertSessionHasErrors('reason');

        // Cannot transfer to same branch
        $this->actingAs($admin)
            ->post(route('hr.applications.transfer-branch', $application), [
                'to_branch_id' => $branchOld->id,
                'reason' => 'Chuyển cùng cơ sở',
            ])
            ->assertSessionHasErrors('to_branch_id');

        // Cannot transfer to inactive branch
        $this->actingAs($admin)
            ->post(route('hr.applications.transfer-branch', $application), [
                'to_branch_id' => $branchInactive->id,
                'reason' => 'Chuyển tới cơ sở bị khóa',
            ])
            ->assertSessionHasErrors('to_branch_id');

        // Cannot transfer to trashed branch
        $this->actingAs($admin)
            ->post(route('hr.applications.transfer-branch', $application), [
                'to_branch_id' => $branchTrashed->id,
                'reason' => 'Chuyển tới cơ sở đã xóa',
            ])
            ->assertSessionHasErrors('to_branch_id');
    }

    public function test_transfer_retains_contacts_appointments_notes_and_stage(): void
    {
        $admin = User::factory()->admin()->create();
        $branchOld = Branch::factory()->create(['status' => 'active']);
        $branchNew = Branch::factory()->create(['status' => 'active']);

        $application = Application::factory()->create([
            'owner_branch_id' => $branchOld->id,
            'stage' => 'interview_scheduled',
        ]);

        $contact = ApplicationContactAttempt::factory()->create([
            'application_id' => $application->id,
            'channel' => 'phone',
            'result' => 'reached',
        ]);

        $appointment = ApplicationAppointment::factory()->create([
            'application_id' => $application->id,
            'type' => 'interview',
            'status' => 'scheduled',
        ]);

        $note = ApplicationNote::factory()->create([
            'application_id' => $application->id,
            'user_id' => $admin->id,
            'content' => 'Ghi chú quan trọng trước khi chuyển.',
        ]);

        $this->actingAs($admin)
            ->post(route('hr.applications.transfer-branch', $application), [
                'to_branch_id' => $branchNew->id,
                'reason' => 'Điều chuyển hồ sơ theo chi nhánh tuyển dụng mới.',
            ])
            ->assertRedirect(route('hr.applications.show', $application));

        $fresh = $application->fresh();
        $this->assertSame($branchNew->id, $fresh->owner_branch_id);
        $this->assertSame('interview_scheduled', $fresh->stage);

        // Verify existing contact attempts, appointments, and notes remain intact
        $this->assertDatabaseHas('application_contact_attempts', ['id' => $contact->id, 'application_id' => $application->id]);
        $this->assertDatabaseHas('application_appointments', ['id' => $appointment->id, 'application_id' => $application->id]);
        $this->assertDatabaseHas('application_notes', ['id' => $note->id, 'application_id' => $application->id]);

        // Verify ApplicationBranchHistory record was created
        $this->assertDatabaseHas('application_branch_histories', [
            'application_id' => $application->id,
            'from_branch_id' => $branchOld->id,
            'to_branch_id' => $branchNew->id,
            'transferred_by' => $admin->id,
            'reason' => 'Điều chuyển hồ sơ theo chi nhánh tuyển dụng mới.',
        ]);
    }

    public function test_access_changes_immediately_after_transfer(): void
    {
        $branchOld = Branch::factory()->create(['status' => 'active']);
        $branchNew = Branch::factory()->create(['status' => 'active']);

        $staffOld = User::factory()->create(['branch_id' => $branchOld->id, 'role' => 'staff']);
        $staffNew = User::factory()->create(['branch_id' => $branchNew->id, 'role' => 'staff']);
        $admin = User::factory()->admin()->create();

        $application = Application::factory()->create([
            'owner_branch_id' => $branchOld->id,
        ]);

        // Before transfer: staffOld can view, staffNew gets 403
        $this->actingAs($staffOld)->get(route('hr.applications.show', $application))->assertOk();
        $this->actingAs($staffNew)->get(route('hr.applications.show', $application))->assertForbidden();

        // Perform transfer as admin
        $this->actingAs($admin)->post(route('hr.applications.transfer-branch', $application), [
            'to_branch_id' => $branchNew->id,
            'reason' => 'Cơ sở đổi cơ cấu quản lý',
        ])->assertRedirect();

        // After transfer: staffOld loses access (403), staffNew gains access (200 OK)
        $this->actingAs($staffOld)->get(route('hr.applications.show', $application))->assertForbidden();
        $this->actingAs($staffNew)->get(route('hr.applications.show', $application))->assertOk();

        // Verify listing query scoping
        $responseStaffOld = $this->actingAs($staffOld)->get(route('hr.applications.index'));
        $responseStaffOld->assertDontSee($application->code);

        $responseStaffNew = $this->actingAs($staffNew)->get(route('hr.applications.index'));
        $responseStaffNew->assertSee($application->code);
    }
}
