<?php

namespace App\Http\Requests\Hr\AdministrativeUnit;

use App\Models\AdministrativeUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdministrativeUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AdministrativeUnit::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', Rule::exists(AdministrativeUnit::class, 'id')],
            'type' => ['required', 'string', Rule::in(AdministrativeUnit::TYPES)],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:170', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/'],
            'official_code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from', 'required_if:is_active,0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->hasAny(['parent_id', 'slug', 'official_code'])) {
                return;
            }

            $slugMatch = AdministrativeUnit::query()
                ->when(
                    $this->filled('parent_id'),
                    fn ($query) => $query->where('parent_id', $this->integer('parent_id')),
                    fn ($query) => $query->whereNull('parent_id')
                )
                ->where('slug', $this->string('slug')->toString())
                ->first();

            if (
                $slugMatch
                && $this->filled('official_code')
                && $slugMatch->official_code !== $this->string('official_code')->toString()
            ) {
                $validator->errors()->add(
                    'slug',
                    'Slug đã thuộc một đơn vị hành chính khác tại cùng cấp cha.'
                );
            }
        });
    }
}
