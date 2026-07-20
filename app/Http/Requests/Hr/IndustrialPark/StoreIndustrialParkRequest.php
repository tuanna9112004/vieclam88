<?php

namespace App\Http\Requests\Hr\IndustrialPark;

use App\Models\AdministrativeUnit;
use App\Models\IndustrialPark;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIndustrialParkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', IndustrialPark::class);
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
        ];
    }
}
