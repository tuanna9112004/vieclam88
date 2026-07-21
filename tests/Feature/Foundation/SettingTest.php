<?php

namespace Tests\Feature\Foundation;

use App\Enums\SettingType;
use App\Models\Setting;
use Database\Seeders\SettingSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_key_must_be_unique(): void
    {
        Setting::factory()->create(['key' => 'dup-key']);

        $this->expectException(QueryException::class);

        Setting::factory()->create(['key' => 'dup-key']);
    }

    public function test_value_is_nullable(): void
    {
        $setting = Setting::factory()->create(['value' => null]);

        $this->assertNull($setting->fresh()->value);
    }

    public function test_type_defaults_to_string(): void
    {
        $setting = Setting::factory()->create();

        $this->assertSame(SettingType::Str, $setting->fresh()->type);
    }

    public function test_seeder_creates_required_job_verification_settings(): void
    {
        (new SettingSeeder())->run();

        $this->assertDatabaseHas('settings', ['key' => 'job_verification_warning_days', 'value' => '7', 'type' => 'integer']);
        $this->assertDatabaseHas('settings', ['key' => 'job_auto_pause_days', 'value' => '14', 'type' => 'integer']);
        $this->assertDatabaseHas('settings', ['key' => 'job_auto_pause_enabled', 'value' => 'false', 'type' => 'boolean']);
        $this->assertDatabaseHas('settings', ['key' => 'job_verification_valid_days', 'value' => null, 'type' => 'integer']);
    }

    public function test_seeder_is_idempotent(): void
    {
        (new SettingSeeder())->run();
        (new SettingSeeder())->run();

        $this->assertSame(4, Setting::count());
    }
}
