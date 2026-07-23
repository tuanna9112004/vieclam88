<?php

namespace Tests\Feature\Hr\Staff;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_staff_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('hr.staff.index'))->assertOk();
    }

    public function test_staff_cannot_view_staff_index(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('hr.staff.index'))->assertForbidden();
    }

    public function test_branch_admin_sees_only_staff_from_own_branch(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $branchAdmin = User::factory()->branchAdmin()->create(['branch_id' => $ownBranch->id]);
        $ownStaff = User::factory()->create([
            'branch_id' => $ownBranch->id,
            'name' => 'Nhân viên cùng cơ sở',
        ]);
        $otherStaff = User::factory()->create([
            'branch_id' => $otherBranch->id,
            'name' => 'Nhân viên khác cơ sở',
        ]);

        $response = $this->actingAs($branchAdmin)->get(route('hr.staff.index'));

        $response->assertOk()
            ->assertSee($ownStaff->name)
            ->assertDontSee($otherStaff->name);
    }

    public function test_guest_is_redirected_from_staff_index(): void
    {
        $this->get(route('hr.staff.index'))->assertRedirect(route('hr.login'));
    }

    public function test_admin_can_create_staff_with_active_branch(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin)->post(route('hr.staff.store'), [
            'name' => 'Nguyễn Văn A',
            'email' => 'staff-a@vieclam88.test',
            'password' => 'temp-password-123',
            'branch_id' => $branch->id,
        ]);

        $response->assertRedirect(route('hr.staff.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'staff-a@vieclam88.test',
            'role' => 'staff',
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);

        $created = User::where('email', 'staff-a@vieclam88.test')->first();
        $this->assertNull($created->password_changed_at);
    }

    public function test_creating_staff_requires_an_active_branch(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($admin)->post(route('hr.staff.store'), [
            'name' => 'Nguyễn Văn A',
            'email' => 'staff-a@vieclam88.test',
            'password' => 'temp-password-123',
            'branch_id' => $branch->id,
        ]);

        $response->assertSessionHasErrors('branch_id');
        $this->assertDatabaseMissing('users', ['email' => 'staff-a@vieclam88.test']);
    }

    public function test_staff_route_rejects_super_admin_role_from_client(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin)->post(route('hr.staff.store'), [
            'name' => 'Nguyễn Văn A',
            'email' => 'staff-a@vieclam88.test',
            'password' => 'temp-password-123',
            'branch_id' => $branch->id,
            'role' => 'super_admin',
            'status' => 'locked',
            'password_changed_at' => now()->toDateTimeString(),
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'staff-a@vieclam88.test']);
    }

    public function test_super_admin_can_create_branch_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $branch = Branch::factory()->create(['status' => 'active']);

        $this->actingAs($superAdmin)->post(route('hr.staff.store'), [
            'name' => 'Quản trị cơ sở',
            'email' => 'branch-admin@vieclam88.test',
            'password' => 'temp-password-123',
            'role' => 'branch_admin',
            'branch_id' => $branch->id,
        ])->assertRedirect(route('hr.staff.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'branch-admin@vieclam88.test',
            'role' => 'branch_admin',
            'branch_id' => $branch->id,
        ]);
    }

    public function test_staff_cannot_create_staff(): void
    {
        $staff = User::factory()->create();
        $branch = Branch::factory()->create(['status' => 'active']);

        $response = $this->actingAs($staff)->post(route('hr.staff.store'), [
            'name' => 'Nguyễn Văn A',
            'email' => 'staff-a@vieclam88.test',
            'password' => 'temp-password-123',
            'branch_id' => $branch->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'staff-a@vieclam88.test']);
    }

    public function test_branch_admin_creates_staff_only_in_own_branch(): void
    {
        $ownBranch = Branch::factory()->create(['status' => 'active']);
        $otherBranch = Branch::factory()->create(['status' => 'active']);
        $branchAdmin = User::factory()->branchAdmin()->create(['branch_id' => $ownBranch->id]);

        $response = $this->actingAs($branchAdmin)->post(route('hr.staff.store'), [
            'name' => 'Nhân viên cơ sở',
            'email' => 'branch-staff@vieclam88.test',
            'password' => 'temp-password-123',
            'branch_id' => $otherBranch->id,
        ]);

        $response->assertSessionHasErrors('branch_id');
        $this->assertDatabaseMissing('users', ['email' => 'branch-staff@vieclam88.test']);

        $this->actingAs($branchAdmin)->post(route('hr.staff.store'), [
            'name' => 'Nhân viên cơ sở',
            'email' => 'branch-staff@vieclam88.test',
            'password' => 'temp-password-123',
            'branch_id' => $ownBranch->id,
        ])->assertRedirect(route('hr.staff.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'branch-staff@vieclam88.test',
            'role' => 'staff',
            'branch_id' => $ownBranch->id,
        ]);
    }

    public function test_branch_admin_cannot_manage_staff_from_another_branch(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $branchAdmin = User::factory()->branchAdmin()->create(['branch_id' => $ownBranch->id]);
        $otherStaff = User::factory()->create(['branch_id' => $otherBranch->id]);

        $this->actingAs($branchAdmin)
            ->get(route('hr.staff.edit', $otherStaff))
            ->assertForbidden();

        $this->actingAs($branchAdmin)
            ->post(route('hr.staff.lock', $otherStaff))
            ->assertForbidden();

        $this->assertSame('active', $otherStaff->fresh()->status);
    }

    public function test_admin_can_update_staff_branch(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();
        $newBranch = Branch::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin)->put(route('hr.staff.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'branch_id' => $newBranch->id,
        ]);

        $response->assertRedirect(route('hr.staff.index'));
        $this->assertSame($newBranch->id, $staff->fresh()->branch_id);
    }

    public function test_updating_staff_requires_an_active_branch(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();
        $inactiveBranch = Branch::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($admin)->put(route('hr.staff.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'branch_id' => $inactiveBranch->id,
        ]);

        $response->assertSessionHasErrors('branch_id');
    }

    public function test_staff_cannot_change_own_or_another_staff_branch(): void
    {
        $staff = User::factory()->create();
        $otherStaff = User::factory()->create();
        $newBranch = Branch::factory()->create(['status' => 'active']);

        $selfResponse = $this->actingAs($staff)->put(route('hr.staff.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'branch_id' => $newBranch->id,
        ]);
        $selfResponse->assertForbidden();

        $otherResponse = $this->actingAs($staff)->put(route('hr.staff.update', $otherStaff), [
            'name' => $otherStaff->name,
            'email' => $otherStaff->email,
            'branch_id' => $newBranch->id,
        ]);
        $otherResponse->assertForbidden();
    }

    public function test_admin_can_lock_and_unlock_staff(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        $this->actingAs($admin)->post(route('hr.staff.lock', $staff))
            ->assertRedirect(route('hr.staff.index'));
        $this->assertSame('locked', $staff->fresh()->status);

        $this->actingAs($admin)->post(route('hr.staff.unlock', $staff))
            ->assertRedirect(route('hr.staff.index'));
        $this->assertSame('active', $staff->fresh()->status);
    }

    public function test_staff_cannot_lock_or_unlock_anyone(): void
    {
        $staff = User::factory()->create();
        $otherStaff = User::factory()->create();

        $this->actingAs($staff)->post(route('hr.staff.lock', $otherStaff))->assertForbidden();
        $this->assertSame('active', $otherStaff->fresh()->status);
    }

    public function test_admin_cannot_lock_or_edit_another_admin_through_staff_routes(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('hr.staff.lock', $otherAdmin))->assertForbidden();
        $this->assertSame('active', $otherAdmin->fresh()->status);

        $this->actingAs($admin)->get(route('hr.staff.edit', $otherAdmin))->assertForbidden();
    }

    public function test_locked_staff_loses_access_on_next_request(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        $this->actingAs($admin)->post(route('hr.staff.lock', $staff));

        $this->actingAs($staff->fresh())->get(route('hr.dashboard'))
            ->assertRedirect(route('hr.login'));
    }
}
