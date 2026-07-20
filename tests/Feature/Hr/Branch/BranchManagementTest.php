<?php

namespace Tests\Feature\Hr\Branch;

use App\Models\AdministrativeUnit;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_branch_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('hr.branches.index'))->assertOk();
    }

    public function test_staff_cannot_view_branch_index(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('hr.branches.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_from_branch_index(): void
    {
        $this->get(route('hr.branches.index'))->assertRedirect(route('hr.login'));
    }

    public function test_admin_can_create_branch(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->post(route('hr.branches.store'), [
            'code' => 'HN-01',
            'name' => 'Chi nhánh Hà Nội',
            'administrative_unit_id' => $unit->id,
        ]);

        $response->assertRedirect(route('hr.branches.index'));
        $this->assertDatabaseHas('branches', ['code' => 'HN-01', 'name' => 'Chi nhánh Hà Nội', 'status' => 'active']);
    }

    public function test_admin_can_create_a_second_branch_with_different_code(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);
        Branch::factory()->create(['code' => 'HN-01', 'administrative_unit_id' => $unit->id]);

        $response = $this->actingAs($admin)->post(route('hr.branches.store'), [
            'code' => 'HCM-01',
            'name' => 'Chi nhánh Hồ Chí Minh',
            'administrative_unit_id' => $unit->id,
        ]);

        $response->assertRedirect(route('hr.branches.index'));
        $this->assertDatabaseHas('branches', ['code' => 'HCM-01']);
        $this->assertDatabaseHas('branches', ['code' => 'HN-01']);
    }

    public function test_creating_branch_requires_unique_code(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);
        Branch::factory()->create(['code' => 'HN-01']);

        $response = $this->actingAs($admin)->post(route('hr.branches.store'), [
            'code' => 'HN-01',
            'name' => 'Chi nhánh trùng mã',
            'administrative_unit_id' => $unit->id,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_creating_branch_requires_name(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->post(route('hr.branches.store'), [
            'code' => 'HN-02',
            'administrative_unit_id' => $unit->id,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_staff_cannot_create_branch(): void
    {
        $staff = User::factory()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);

        $response = $this->actingAs($staff)->post(route('hr.branches.store'), [
            'code' => 'HN-01',
            'name' => 'Chi nhánh Hà Nội',
            'administrative_unit_id' => $unit->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('branches', ['code' => 'HN-01']);
    }

    public function test_admin_can_update_branch(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin)->put(route('hr.branches.update', $branch), [
            'code' => $branch->code,
            'name' => 'Tên mới',
            'administrative_unit_id' => $branch->administrative_unit_id,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('hr.branches.index'));
        $this->assertSame('Tên mới', $branch->fresh()->name);
    }

    public function test_admin_can_deactivate_branch_without_staff(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin)->put(route('hr.branches.update', $branch), [
            'code' => $branch->code,
            'name' => $branch->name,
            'administrative_unit_id' => $branch->administrative_unit_id,
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('hr.branches.index'));
        $this->assertSame('inactive', $branch->fresh()->status);
    }

    public function test_cannot_deactivate_branch_with_staff_assigned(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create(['status' => 'active']);
        User::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($admin)->put(route('hr.branches.update', $branch), [
            'code' => $branch->code,
            'name' => $branch->name,
            'administrative_unit_id' => $branch->administrative_unit_id,
            'status' => 'inactive',
        ]);

        $response->assertSessionHasErrors();
        $this->assertSame('active', $branch->fresh()->status);
    }

    public function test_staff_cannot_update_branch(): void
    {
        $staff = User::factory()->create();
        $branch = Branch::factory()->create(['status' => 'active']);

        $response = $this->actingAs($staff)->put(route('hr.branches.update', $branch), [
            'code' => $branch->code,
            'name' => 'Tên mới',
            'administrative_unit_id' => $branch->administrative_unit_id,
            'status' => 'active',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_soft_delete_branch_without_staff(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create();

        $response = $this->actingAs($admin)->delete(route('hr.branches.destroy', $branch));

        $response->assertRedirect(route('hr.branches.index'));
        $this->assertSoftDeleted('branches', ['id' => $branch->id]);
    }

    public function test_cannot_delete_branch_with_staff_assigned(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create();
        User::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($admin)->delete(route('hr.branches.destroy', $branch));

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'deleted_at' => null]);
    }

    public function test_staff_cannot_delete_branch(): void
    {
        $staff = User::factory()->create();
        $branch = Branch::factory()->create();

        $response = $this->actingAs($staff)->delete(route('hr.branches.destroy', $branch));

        $response->assertForbidden();
        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'deleted_at' => null]);
    }

    public function test_admin_can_restore_soft_deleted_branch(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create();
        $branch->delete();

        $response = $this->actingAs($admin)->post(route('hr.branches.restore', $branch));

        $response->assertRedirect(route('hr.branches.index'));
        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'deleted_at' => null]);
    }

    public function test_staff_cannot_restore_branch(): void
    {
        $staff = User::factory()->create();
        $branch = Branch::factory()->create();
        $branch->delete();

        $response = $this->actingAs($staff)->post(route('hr.branches.restore', $branch));

        $response->assertForbidden();
        $this->assertSoftDeleted('branches', ['id' => $branch->id]);
    }

    public function test_soft_deleted_branch_cannot_be_used_to_assign_new_staff(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create(['status' => 'active']);
        $branch->delete();

        $response = $this->actingAs($admin)->post(route('hr.staff.store'), [
            'name' => 'Nguyễn Văn A',
            'email' => 'staff-soft-deleted-branch@vieclam88.test',
            'password' => 'temp-password-123',
            'branch_id' => $branch->id,
        ]);

        $response->assertSessionHasErrors('branch_id');
        $this->assertDatabaseMissing('users', ['email' => 'staff-soft-deleted-branch@vieclam88.test']);
    }
}
