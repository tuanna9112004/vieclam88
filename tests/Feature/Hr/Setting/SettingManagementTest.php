<?php

namespace Tests\Feature\Hr\Setting;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SettingManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        $payload = [
            'settings' => [
                'job_verification_warning_days' => ['type' => 'integer', 'value' => 7],
                'job_auto_pause_days' => ['type' => 'integer', 'value' => 14],
                'job_auto_pause_enabled' => ['type' => 'boolean', 'value' => 0],
                'job_verification_valid_days' => ['type' => 'integer', 'value' => null],
            ],
        ];

        return array_replace_recursive($payload, $overrides);
    }

    public function test_route_map_setting_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('hr.settings.index'));
        $this->assertTrue(Route::has('hr.settings.update'));
    }

    public function test_guest_is_redirected_from_setting_routes(): void
    {
        $this->get(route('hr.settings.index'))->assertRedirect(route('hr.login'));
        $this->put(route('hr.settings.update'), $this->validPayload())->assertRedirect(route('hr.login'));
    }

    public function test_staff_direct_url_is_forbidden_for_index_and_update(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('hr.settings.index'))->assertForbidden();
        $this->actingAs($staff)->put(route('hr.settings.update'), $this->validPayload())->assertForbidden();
        $this->assertSame(0, Setting::count());
    }

    public function test_admin_index_only_displays_phase_one_allowlist_and_never_displays_secret_setting(): void
    {
        $admin = User::factory()->admin()->create();
        Setting::factory()->create([
            'key' => 'smtp_password',
            'value' => 'super-secret-value',
            'type' => 'string',
        ]);

        $response = $this->actingAs($admin)->get(route('hr.settings.index'))->assertOk();

        foreach ([
            'job_verification_warning_days',
            'job_auto_pause_days',
            'job_auto_pause_enabled',
            'job_verification_valid_days',
        ] as $key) {
            $response->assertSee($key);
        }

        $response->assertDontSee('smtp_password');
        $response->assertDontSee('super-secret-value');
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_admin_can_update_all_whitelisted_settings_with_server_owned_types(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put(route('hr.settings.update'), $this->validPayload([
            'settings' => [
                'job_verification_warning_days' => ['value' => 10],
                'job_auto_pause_days' => ['value' => 20],
                'job_auto_pause_enabled' => ['value' => 1],
                'job_verification_valid_days' => ['value' => 30],
            ],
        ]));

        $response->assertRedirect(route('hr.settings.index'));
        $response->assertSessionHas('status');
        $this->assertSame(4, Setting::count());
        $this->assertDatabaseHas('settings', [
            'key' => 'job_verification_warning_days',
            'value' => '10',
            'type' => 'integer',
            'group_name' => 'job_verification',
            'is_public' => false,
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'job_auto_pause_enabled',
            'value' => 'true',
            'type' => 'boolean',
            'is_public' => false,
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'job_verification_valid_days',
            'value' => '30',
            'type' => 'integer',
        ]);
    }

    public function test_update_rejects_unknown_or_secret_key_without_writing_any_setting(): void
    {
        $admin = User::factory()->admin()->create();
        Setting::factory()->create([
            'key' => 'smtp_password',
            'value' => 'original-secret',
            'type' => 'string',
        ]);

        $response = $this->actingAs($admin)->put(route('hr.settings.update'), $this->validPayload([
            'settings' => [
                'smtp_password' => ['type' => 'string', 'value' => 'hacked-secret'],
            ],
        ]));

        $response->assertSessionHasErrors('settings');
        $this->assertSame('original-secret', Setting::where('key', 'smtp_password')->value('value'));
        $this->assertSame(1, Setting::count());
    }

    public function test_update_rejects_type_tampering_and_invalid_value_ranges(): void
    {
        $admin = User::factory()->admin()->create();

        $typeResponse = $this->actingAs($admin)->put(route('hr.settings.update'), $this->validPayload([
            'settings' => [
                'job_verification_warning_days' => ['type' => 'string'],
            ],
        ]));
        $typeResponse->assertSessionHasErrors('settings.job_verification_warning_days.type');

        $rangeResponse = $this->actingAs($admin)->put(route('hr.settings.update'), $this->validPayload([
            'settings' => [
                'job_verification_warning_days' => ['value' => 20],
                'job_auto_pause_days' => ['value' => 10],
            ],
        ]));
        $rangeResponse->assertSessionHasErrors('settings.job_auto_pause_days.value');

        $this->assertSame(0, Setting::count());
    }
}
