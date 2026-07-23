<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Setting\UpdatePhaseOneSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Setting\UpdateSettingsRequest;
use App\Models\Setting;
use App\Support\PhaseOneSettingCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Setting::class);

        $storedSettings = Setting::query()
            ->whereIn('key', PhaseOneSettingCatalog::keys())
            ->get()
            ->keyBy('key');

        $settings = collect(PhaseOneSettingCatalog::DEFINITIONS)
            ->map(function (array $definition, string $key) use ($storedSettings): array {
                $stored = $storedSettings->get($key);

                return [
                    'key' => $key,
                    ...$definition,
                    'value' => $stored?->value ?? $definition['default'],
                ];
            });

        return view('hr.settings.index', ['settings' => $settings]);
    }

    public function update(
        UpdateSettingsRequest $request,
        UpdatePhaseOneSettingsAction $action
    ): RedirectResponse {
        $action->handle($request->validated('settings'), $request->user());

        return redirect()->route('hr.settings.index')->with('status', 'Đã cập nhật cấu hình Phase 1.');
    }
}
