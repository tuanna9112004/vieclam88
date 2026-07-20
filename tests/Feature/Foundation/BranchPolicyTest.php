<?php

namespace Tests\Feature\Foundation;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_branches(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create();

        $this->assertTrue($admin->can('viewAny', Branch::class));
        $this->assertTrue($admin->can('create', Branch::class));
        $this->assertTrue($admin->can('update', $branch));
        $this->assertTrue($admin->can('delete', $branch));
        $this->assertTrue($admin->can('restore', $branch));
    }

    public function test_staff_cannot_manage_branches(): void
    {
        $staff = User::factory()->create();
        $branch = Branch::factory()->create();

        $this->assertFalse($staff->can('viewAny', Branch::class));
        $this->assertFalse($staff->can('create', Branch::class));
        $this->assertFalse($staff->can('update', $branch));
        $this->assertFalse($staff->can('delete', $branch));
        $this->assertFalse($staff->can('restore', $branch));
    }
}
