<?php

namespace Tests\Feature\Foundation;

use App\Actions\User\CreateStaffAction;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateStaffActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_staff_with_branch(): void
    {
        $branch = Branch::factory()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $staff = (new CreateStaffAction)->handle([
            'name' => 'Nguyễn Văn A',
            'email' => 'a@vieclam88.test',
            'branch_id' => $branch->id,
            'password' => 'temp-password-123',
        ], $superAdmin);

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'role' => 'staff',
            'branch_id' => $branch->id,
        ]);
        $this->assertNull($staff->password_changed_at);
    }

    public function test_rejects_staff_without_branch(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->expectException(ValidationException::class);

        (new CreateStaffAction)->handle([
            'name' => 'Nguyễn Văn A',
            'email' => 'a@vieclam88.test',
            'branch_id' => null,
            'password' => 'temp-password-123',
        ], $superAdmin);
    }

    public function test_branch_admin_can_only_create_staff_in_own_active_branch(): void
    {
        $ownBranch = Branch::factory()->create(['status' => 'active']);
        $otherBranch = Branch::factory()->create(['status' => 'active']);
        $branchAdmin = User::factory()->branchAdmin()->create(['branch_id' => $ownBranch->id]);

        $staff = (new CreateStaffAction)->handle([
            'name' => 'Nguyễn Văn A',
            'email' => 'a@vieclam88.test',
            'branch_id' => $otherBranch->id,
            'password' => 'temp-password-123',
        ], $branchAdmin);

        $this->assertSame($ownBranch->id, $staff->branch_id);
        $this->assertTrue($staff->isStaff());
    }

    public function test_super_admin_can_create_branch_admin(): void
    {
        $branch = Branch::factory()->create(['status' => 'active']);
        $superAdmin = User::factory()->superAdmin()->create();

        $branchAdmin = (new CreateStaffAction)->handle([
            'name' => 'Quản trị cơ sở',
            'email' => 'branch-admin@vieclam88.test',
            'branch_id' => $branch->id,
            'password' => 'temp-password-123',
            'role' => 'branch_admin',
        ], $superAdmin);

        $this->assertTrue($branchAdmin->isBranchAdmin());
        $this->assertSame($branch->id, $branchAdmin->branch_id);
    }

    public function test_staff_cannot_create_staff_through_action(): void
    {
        $staffActor = User::factory()->create();

        $this->expectException(AuthorizationException::class);

        (new CreateStaffAction)->handle([
            'name' => 'Nguyễn Văn A',
            'email' => 'a@vieclam88.test',
            'branch_id' => $staffActor->branch_id,
            'password' => 'temp-password-123',
        ], $staffActor);
    }
}
