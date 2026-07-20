<?php

namespace Tests\Feature\Hr\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrSessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_locked_mid_session_loses_access_on_next_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $user->update(['status' => 'locked']);

        $response = $this->get(route('hr.dashboard'));

        $response->assertRedirect(route('hr.login'));
        $this->assertGuest();
    }

    public function test_account_locked_mid_session_is_invalidated_not_just_blocked_once(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $user->update(['status' => 'locked']);

        $this->get(route('hr.dashboard'))->assertRedirect(route('hr.login'));

        $response = $this->get(route('hr.dashboard'));

        $response->assertRedirect(route('hr.login'));
        $this->assertGuest();
    }

    public function test_account_locked_mid_session_loses_access_to_any_hr_route_not_just_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $user->update(['status' => 'locked']);

        $response = $this->get(route('hr.password.change'));

        $response->assertRedirect(route('hr.login'));
        $this->assertGuest();
    }

    public function test_account_locked_mid_session_posting_logout_still_ends_up_logged_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $user->update(['status' => 'locked']);

        $response = $this->post(route('hr.logout'));

        $response->assertRedirect(route('hr.login'));
        $this->assertGuest();
    }
}
