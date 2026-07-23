<?php

namespace Tests\Feature\Foundation;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserRoleMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_backfills_legacy_admin_to_super_admin(): void
    {
        $migration = require database_path('migrations/2025_02_02_000001_expand_user_roles_to_three_levels.php');
        $migration->down();

        $userId = DB::table('users')->insertGetId([
            'role' => 'admin',
            'branch_id' => Branch::factory()->create()->id,
            'name' => 'Legacy Admin',
            'email' => 'legacy-admin@vieclam88.test',
            'password' => 'hashed',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration->up();

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'role' => 'super_admin',
            'branch_id' => null,
        ]);
    }

    public function test_rollback_maps_super_admin_to_legacy_admin_and_can_redeploy(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $migration = require database_path('migrations/2025_02_02_000001_expand_user_roles_to_three_levels.php');

        $migration->down();

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'role' => 'admin']);

        $migration->up();

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'role' => 'super_admin']);
    }

    public function test_rollback_fails_closed_while_branch_admin_accounts_exist(): void
    {
        $branchAdmin = User::factory()->branchAdmin()->create();
        $migration = require database_path('migrations/2025_02_02_000001_expand_user_roles_to_three_levels.php');

        try {
            $migration->down();
            $this->fail('Rollback phải bị chặn khi còn branch_admin.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Không thể rollback role khi còn branch_admin', $exception->getMessage());
        }

        $this->assertDatabaseHas('users', ['id' => $branchAdmin->id, 'role' => 'branch_admin']);
    }
}
