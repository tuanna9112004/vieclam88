<?php

namespace App\Http\Requests\Hr\CompanyLocation;

use App\Models\AdministrativeUnit;
use App\Models\CompanyLocation;
use App\Models\IndustrialPark;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CompanyLocation $location */
        $location = $this->route('location');

        return $this->user()->can('update', $location);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'administrative_unit_id' => ['nullable', Rule::exists(AdministrativeUnit::class, 'id')->where('is_active', true)],
            'industrial_park_id' => ['nullable', Rule::exists(IndustrialPark::class, 'id')->where('is_active', true)],
            'address_detail' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->guardProvinceMatchesIndustrialPark($validator);
        });
    }

    /**
     * ADR-052: khi có `industrial_park_id`, `administrative_unit_id` bắt buộc khác null và
     * đúng bằng đơn vị hành chính của KCN đó.
     */
    protected function guardProvinceMatchesIndustrialPark(Validator $validator): void
    {
        $industrialParkId = $this->input('industrial_park_id');

        if (empty($industrialParkId)) {
            return;
        }

        $park = IndustrialPark::find($industrialParkId);

        if (! $park) {
            return;
        }

        if ((int) $this->input('administrative_unit_id') !== $park->administrative_unit_id) {
            $validator->errors()->add(
                'administrative_unit_id',
                'Tỉnh/thành phải khớp với khu công nghiệp đã chọn.'
            );
        }
    }
}
