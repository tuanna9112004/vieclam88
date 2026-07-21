<?php

namespace App\Http\Requests\Hr\Company;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Company::class);
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
