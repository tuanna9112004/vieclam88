<?php

namespace Tests\Feature\Hr;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_temporary_password_is_redirected_to_password_change(): void
    {
        $user = User::factory()->passwordNotChanged()->create();

        $response = $this->actingAs($user)->get(route('hr.dashboard'));

        $response->assertRedirect(route('hr.password.change'));
    }

    public function test_user_with_temporary_password_can_reach_password_change_and_logout(): void
    {
        $user = User::factory()->passwordNotChanged()->create();

        $this->actingAs($user)->get(route('hr.password.change'))->assertOk();

        $response = $this->actingAs($user)->post(route('hr.logout'));
        $response->assertRedirect(route('hr.login'));
    }

    public function test_user_can_set_new_password_and_is_no_longer_gated(): void
    {
        $user = User::factory()->passwordNotChanged()->create();

        $response = $this->actingAs($user)->put(route('hr.password.update'), [
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $response->assertRedirect(route('hr.dashboard'));

        $user->refresh();
        $this->assertNotNull($user->password_changed_at);
        $this->assertTrue(Hash::check('new-secure-password', $user->password));

        $this->get(route('hr.dashboard'))->assertOk();
    }

    public function test_password_update_requires_confirmation_match(): void
    {
        $user = User::factory()->passwordNotChanged()->create();

        $response = $this->actingAs($user)->put(route('hr.password.update'), [
            'password' => 'new-secure-password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertNull($user->refresh()->password_changed_at);
    }

    public function test_user_with_changed_password_is_not_redirected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('hr.dashboard'));

        $response->assertOk();
    }
}
