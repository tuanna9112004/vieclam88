<?php

namespace Tests\Feature\Foundation;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'a@vieclam88.test']);

        $this->expectException(QueryException::class);

        User::factory()->create(['email' => 'a@vieclam88.test']);
    }

    public function test_role_only_accepts_three_hr_roles(): void
    {
        $this->expectException(QueryException::class);

        User::factory()->create(['role' => 'candidate']);
    }

    public function test_super_admin_can_have_null_branch_and_legacy_alias(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->assertNull($admin->branch_id);
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->hasValidBranchAssignment());
    }

    public function test_staff_belongs_to_a_branch(): void
    {
        $branch = Branch::factory()->create();
        $staff = User::factory()->create(['branch_id' => $branch->id]);

        $this->assertTrue($staff->branch->is($branch));
        $this->assertFalse($staff->isAdmin());
        $this->assertTrue($staff->hasValidBranchAssignment());
    }

    public function test_branch_admin_can_manage_only_own_active_branch(): void
    {
        $branch = Branch::factory()->create(['status' => 'active']);
        $otherBranch = Branch::factory()->create(['status' => 'active']);
        $branchAdmin = User::factory()->branchAdmin()->create(['branch_id' => $branch->id]);

        $this->assertTrue($branchAdmin->isBranchAdmin());
        $this->assertTrue($branchAdmin->canManageBranch($branch));
        $this->assertFalse($branchAdmin->canManageBranch($otherBranch));

        $branch->update(['status' => 'inactive']);

        $this->assertFalse($branchAdmin->fresh()->hasValidBranchAssignment());
    }

    public function test_deleting_branch_referenced_by_staff_sets_branch_id_null(): void
    {
        $branch = Branch::factory()->create();
        $staff = User::factory()->create(['branch_id' => $branch->id]);

        $branch->forceDelete();

        $this->assertNull($staff->refresh()->branch_id);
    }

    public function test_locked_status_reflected_by_is_active(): void
    {
        $user = User::factory()->locked()->create();

        $this->assertFalse($user->isActive());
    }
}
