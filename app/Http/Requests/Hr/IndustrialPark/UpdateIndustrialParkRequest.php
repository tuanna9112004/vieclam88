<?php

namespace App\Http\Requests\Hr\IndustrialPark;

use App\Models\AdministrativeUnit;
use App\Models\IndustrialPark;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIndustrialParkRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var IndustrialPark $industrialPark */
        $industrialPark = $this->route('industrialPark');

        return $this->user()->can('update', $industrialPark);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'administrative_unit_id' => [
                'required',
                Rule::exists(AdministrativeUnit::class, 'id')->where('is_active', true),
            ],
            'name' => ['required', 'string', 'max:150'],
            'official_name' => ['nullable', 'string', 'max:200'],
            'address_detail' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
