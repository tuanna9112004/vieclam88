<?php

namespace Tests\Feature\Hr\Auth;

use App\Models\Branch;
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

    public function test_branch_role_loses_access_when_branch_becomes_inactive(): void
    {
        $branch = Branch::factory()->create(['status' => 'active']);
        $branchAdmin = User::factory()->branchAdmin()->create(['branch_id' => $branch->id]);
        $this->actingAs($branchAdmin);

        $branch->update(['status' => 'inactive']);

        $this->get(route('hr.dashboard'))->assertRedirect(route('hr.login'));
        $this->assertGuest();
    }

    public function test_branch_role_loses_access_when_branch_is_soft_deleted(): void
    {
        $branch = Branch::factory()->create(['status' => 'active']);
        $branchAdmin = User::factory()->branchAdmin()->create(['branch_id' => $branch->id]);
        $this->actingAs($branchAdmin);

        $branch->delete();

        $this->get(route('hr.dashboard'))->assertRedirect(route('hr.login'));
        $this->assertGuest();
    }
}
