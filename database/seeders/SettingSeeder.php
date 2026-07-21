<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * docs/DATABASE-DICTIONARY.md muc 9.22, docs/CORE-FLOWS.md muc 1.3 — seed bat buoc cho
     * Job Verification Scheduler. job_verification_valid_days = null (tat kiem tra do moi,
     * ADR-058) — gia tri cu the con [CAN CHOT VOI CONG TY], khong chan migration.
     */
    public function run(): void
    {
        $settings = [
            'job_verification_warning_days' => ['value' => '7', 'type' => 'integer'],
            'job_auto_pause_days' => ['value' => '14', 'type' => 'integer'],
            'job_auto_pause_enabled' => ['value' => 'false', 'type' => 'boolean'],
            'job_verification_valid_days' => ['value' => null, 'type' => 'integer'],
        ];

        foreach ($settings as $key => $attributes) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $attributes['value'],
                    'type' => $attributes['type'],
                    'group_name' => 'job_verification',
                    'is_public' => false,
                ]
            );
        }
    }
}
