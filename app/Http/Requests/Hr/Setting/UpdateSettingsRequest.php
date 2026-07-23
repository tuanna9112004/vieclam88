<?php

namespace App\Http\Requests\Hr\Setting;

use App\Models\Setting;
use App\Support\PhaseOneSettingCatalog;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateAny', Setting::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $keys = PhaseOneSettingCatalog::keys();
        $rules = [
            'settings' => ['required', 'array:'.implode(',', $keys), 'size:'.count($keys)],
        ];

        foreach (PhaseOneSettingCatalog::DEFINITIONS as $key => $definition) {
            $path = 'settings.'.$key;
            $rules[$path] = ['required', 'array:type,value'];
            $rules[$path.'.type'] = ['required', 'string', Rule::in([$definition['type']])];

            $valueRules = $definition['nullable'] ? ['nullable'] : ['required'];
            $valueRules[] = $definition['type'] === 'boolean' ? 'boolean' : 'integer';

            if ($definition['type'] === 'integer') {
                $valueRules[] = 'min:1';
                $valueRules[] = 'max:365';
            }

            $rules[$path.'.value'] = $valueRules;
        }

        return $rules;
    }

    /**
     * @return array<int, callable(ValidatorContract): void>
     */
    public function after(): array
    {
        return [
            function (ValidatorContract $validator): void {
                $warningDays = $this->input('settings.job_verification_warning_days.value');
                $criticalDays = $this->input('settings.job_auto_pause_days.value');

                if (is_numeric($warningDays) && is_numeric($criticalDays) && (int) $criticalDays <= (int) $warningDays) {
                    $validator->errors()->add(
                        'settings.job_auto_pause_days.value',
                        'Số ngày cảnh báo mức cao phải lớn hơn số ngày cảnh báo xác minh.'
                    );
                }
            },
        ];
    }
}
