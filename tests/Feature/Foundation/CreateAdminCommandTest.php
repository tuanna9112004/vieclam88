<?php

namespace Tests\Feature\Foundation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_first_admin(): void
    {
        $this->artisan('app:create-admin', [
            '--name' => 'Admin Gốc',
            '--email' => 'admin@vieclam88.test',
            '--password' => 'admin-password-123',
        ])->assertExitCode(0);

        $admin = User::where('email', 'admin@vieclam88.test')->first();

        $this->assertNotNull($admin);
        $this->assertSame('super_admin', $admin->role);
        $this->assertNull($admin->branch_id);
        $this->assertNull($admin->password_changed_at);
        $this->assertTrue(Hash::check('admin-password-123', $admin->password));
    }

    public function test_refuses_second_admin_without_force(): void
    {
        User::factory()->admin()->create(['email' => 'existing-admin@vieclam88.test']);

        $this->artisan('app:create-admin', [
            '--name' => 'Admin 2',
            '--email' => 'admin2@vieclam88.test',
            '--password' => 'admin-password-123',
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'admin2@vieclam88.test']);
    }

    public function test_allows_second_admin_with_force(): void
    {
        User::factory()->admin()->create(['email' => 'existing-admin@vieclam88.test']);

        $this->artisan('app:create-admin', [
            '--name' => 'Admin 2',
            '--email' => 'admin2@vieclam88.test',
            '--password' => 'admin-password-123',
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'admin2@vieclam88.test', 'role' => 'super_admin']);
    }

    public function test_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dup@vieclam88.test']);

        $this->artisan('app:create-admin', [
            '--name' => 'Admin Dup',
            '--email' => 'dup@vieclam88.test',
            '--password' => 'admin-password-123',
        ])->assertExitCode(1);
    }
}
