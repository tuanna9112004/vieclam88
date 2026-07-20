<?php

namespace Tests\Feature\Hr\Staff;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResetStaffPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reset_staff_password(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create(['password' => 'old-password']);

        $response = $this->actingAs($admin)->post(route('hr.staff.reset-password', $staff), [
            'password' => 'new-temp-password',
        ]);

        $response->assertRedirect(route('hr.dashboard'));

        $staff->refresh();
        $this->assertNull($staff->password_changed_at);
        $this->assertTrue(Hash::check('new-temp-password', $staff->password));
    }

    public function test_reset_forces_staff_to_change_password_again(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        $this->actingAs($admin)->post(route('hr.staff.reset-password', $staff), [
            'password' => 'new-temp-password',
        ]);

        $response = $this->actingAs($staff->refresh())->get(route('hr.dashboard'));

        $response->assertRedirect(route('hr.password.change'));
    }

    public function test_staff_cannot_reset_another_staff_password(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();

        $response = $this->actingAs($actor)->post(route('hr.staff.reset-password', $target), [
            'password' => 'new-temp-password',
        ]);

        $response->assertForbidden();
        $this->assertNotNull($target->refresh()->password_changed_at);
    }

    public function test_admin_cannot_reset_another_admin_password(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('hr.staff.reset-password', $otherAdmin), [
            'password' => 'new-temp-password',
        ]);

        $response->assertForbidden();
    }

    public function test_guest_cannot_reset_staff_password(): void
    {
        $staff = User::factory()->create();

        $response = $this->post(route('hr.staff.reset-password', $staff), [
            'password' => 'new-temp-password',
        ]);

        $response->assertRedirect(route('hr.login'));
    }

    public function test_admin_with_unchanged_password_cannot_reset_staff_password(): void
    {
        $admin = User::factory()->admin()->passwordNotChanged()->create();
        $staff = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('hr.staff.reset-password', $staff), [
            'password' => 'new-temp-password',
        ]);

        $response->assertRedirect(route('hr.password.change'));
        $this->assertNotNull($staff->refresh()->password_changed_at);
    }
}
