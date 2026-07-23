<?php

namespace App\Http\Requests\Hr\AdministrativeUnit;

use App\Models\AdministrativeUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdministrativeUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var AdministrativeUnit $administrativeUnit */
        $administrativeUnit = $this->route('administrativeUnit');

        return $this->user()->can('update', $administrativeUnit);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var AdministrativeUnit $administrativeUnit */
        $administrativeUnit = $this->route('administrativeUnit');

        return [
            'parent_id' => ['nullable', 'integer', Rule::exists(AdministrativeUnit::class, 'id')],
            'type' => ['required', 'string', Rule::in(AdministrativeUnit::TYPES)],
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'string',
                'max:170',
                'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::unique(AdministrativeUnit::class, 'slug')
                    ->ignore($administrativeUnit)
                    ->when(
                        $this->filled('parent_id'),
                        fn ($rule) => $rule->where('parent_id', $this->integer('parent_id')),
                        fn ($rule) => $rule->whereNull('parent_id')
                    ),
            ],
            'official_code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique(AdministrativeUnit::class, 'official_code')->ignore($administrativeUnit),
            ],
            'is_active' => ['required', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from', 'required_if:is_active,0'],
        ];
    }
}
