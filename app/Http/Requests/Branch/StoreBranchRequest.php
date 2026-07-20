<?php

namespace App\Http\Requests\Branch;

use App\Models\AdministrativeUnit;
use App\Models\Branch;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Branch::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:branches,code'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'zalo' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:191'],
            'administrative_unit_id' => [
                'required',
                Rule::exists(AdministrativeUnit::class, 'id')->where('is_active', true),
            ],
            'address_detail' => ['nullable', 'string', 'max:255'],
        ];
    }
}
