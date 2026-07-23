<?php

namespace Tests\Feature\Hr\Auth;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class HrLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $response = $this->post(route('hr.login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('hr.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_login(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'correct-password']);

        $response = $this->post(route('hr.login.store'), [
            'email' => $admin->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('hr.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_branch_admin_can_login_with_active_branch(): void
    {
        $branchAdmin = User::factory()->branchAdmin()->create(['password' => 'correct-password']);

        $this->post(route('hr.login.store'), [
            'email' => $branchAdmin->email,
            'password' => 'correct-password',
        ])->assertRedirect(route('hr.dashboard'));

        $this->assertAuthenticatedAs($branchAdmin);
    }

    public function test_branch_role_cannot_login_with_inactive_branch(): void
    {
        $branch = Branch::factory()->create(['status' => 'inactive']);
        $branchAdmin = User::factory()->branchAdmin()->create([
            'branch_id' => $branch->id,
            'password' => 'correct-password',
        ]);

        $this->post(route('hr.login.store'), [
            'email' => $branchAdmin->email,
            'password' => 'correct-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_branch_role_cannot_login_with_soft_deleted_branch(): void
    {
        $branch = Branch::factory()->create(['status' => 'active']);
        $branchAdmin = User::factory()->branchAdmin()->create([
            'branch_id' => $branch->id,
            'password' => 'correct-password',
        ]);
        $branch->delete();

        $this->post(route('hr.login.store'), [
            'email' => $branchAdmin->email,
            'password' => 'correct-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_super_admin_cannot_login_when_assigned_to_branch(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'branch_id' => Branch::factory(),
            'password' => 'correct-password',
        ]);

        $this->post(route('hr.login.store'), [
            'email' => $superAdmin->email,
            'password' => 'correct-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_regenerates_session_id(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $this->get(route('hr.login'));
        $idBefore = $this->app['session']->getId();

        $this->post(route('hr.login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $idAfter = $this->app['session']->getId();

        $this->assertNotSame($idBefore, $idAfter);
    }

    public function test_wrong_password_cannot_login(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $response = $this->post(route('hr.login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_locked_account_cannot_login(): void
    {
        $user = User::factory()->locked()->create(['password' => 'correct-password']);

        $response = $this->post(route('hr.login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_too_many_attempts(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('hr.login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('hr.login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        RateLimiter::clear(mb_strtolower($user->email).'|127.0.0.1');
    }

    public function test_guest_cannot_access_hr_dashboard(): void
    {
        $response = $this->get(route('hr.dashboard'));

        $response->assertRedirect(route('hr.login'));
    }

    public function test_authenticated_user_redirected_away_from_login_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('hr.login'));

        $response->assertRedirect(route('hr.dashboard'));
    }

    public function test_logout_invalidates_session_and_requires_login_again(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('hr.logout'));

        $response->assertRedirect(route('hr.login'));
        $this->assertGuest();

        $this->get(route('hr.dashboard'))->assertRedirect(route('hr.login'));
    }

    public function test_guest_cannot_logout(): void
    {
        $response = $this->post(route('hr.logout'));

        $response->assertRedirect(route('hr.login'));
        $this->assertGuest();
    }
}
