<?php

namespace App\Http\Requests\Hr\Company;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Company $company */
        $company = $this->route('company');

        return $this->user()->can('update', $company);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'short_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'industry' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }
}
