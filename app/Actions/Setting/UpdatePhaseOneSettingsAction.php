<?php

namespace App\Actions\Setting;

use App\Models\Setting;
use App\Models\User;
use App\Support\PhaseOneSettingCatalog;
use Illuminate\Support\Facades\DB;

class UpdatePhaseOneSettingsAction
{
    /**
     * @param  array<string, array{type: string, value: mixed}>  $submittedSettings
     */
    public function handle(array $submittedSettings, User $actor): void
    {
        abort_unless($actor->isSuperAdmin(), 403);

        DB::transaction(function () use ($submittedSettings): void {
            foreach (PhaseOneSettingCatalog::DEFINITIONS as $key => $definition) {
                $setting = Setting::query()
                    ->where('key', $key)
                    ->lockForUpdate()
                    ->first() ?? new Setting(['key' => $key]);

                $setting->value = $this->normalizeValue(
                    $definition['type'],
                    $submittedSettings[$key]['value'] ?? null
                );
                // Kiểu dữ liệu luôn lấy từ allowlist server, không tin hidden input của form.
                $setting->type = $definition['type'];

                if (! $setting->exists) {
                    $setting->group_name = 'job_verification';
                    $setting->is_public = false;
                }

                $setting->save();
            }
        });
    }

    private function normalizeValue(string $type, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($type) {
            'boolean' => (bool) $value ? 'true' : 'false',
            'integer' => (string) (int) $value,
            default => (string) $value,
        };
    }
}
