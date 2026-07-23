<?php

namespace App\Http\Requests\Hr\AdministrativeUnit;

use App\Models\AdministrativeUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAdministrativeUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', AdministrativeUnit::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:150'],
            'edit' => ['nullable', 'integer', Rule::exists(AdministrativeUnit::class, 'id')],
        ];
    }
}
