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

    public function test_role_only_accepts_staff_or_admin(): void
    {
        $this->expectException(QueryException::class);

        User::factory()->create(['role' => 'candidate']);
    }

    public function test_admin_can_have_null_branch(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertNull($admin->branch_id);
        $this->assertTrue($admin->isAdmin());
    }

    public function test_staff_belongs_to_a_branch(): void
    {
        $branch = Branch::factory()->create();
        $staff = User::factory()->create(['branch_id' => $branch->id]);

        $this->assertTrue($staff->branch->is($branch));
        $this->assertFalse($staff->isAdmin());
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
