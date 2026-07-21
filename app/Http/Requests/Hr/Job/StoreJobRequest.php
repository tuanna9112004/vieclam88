<?php

namespace App\Http\Requests\Hr\Job;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\Job;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Job::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:200'],
            'company_id' => ['required', Rule::exists(Company::class, 'id')->withoutTrashed()],
            'company_location_id' => ['nullable', Rule::exists(CompanyLocation::class, 'id')->withoutTrashed()],
        ];

        // Staff tự động gán owner_branch_id = branch của mình (docs/CORE-FLOWS.md mục 1.1) —
        // không đọc từ input, form không hiển thị field này cho Staff. Chỉ Admin bắt buộc chọn.
        if ($this->user()->isAdmin()) {
            $rules['owner_branch_id'] = [
                'required',
                Rule::exists(Branch::class, 'id')->where('status', 'active')->withoutTrashed(),
            ];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->guardLocationBelongsToCompany($validator);
        });
    }

    protected function guardLocationBelongsToCompany(Validator $validator): void
    {
        $locationId = $this->input('company_location_id');

        if (empty($locationId)) {
            return;
        }

        $location = CompanyLocation::find($locationId);

        if ($location && (int) $location->company_id !== (int) $this->input('company_id')) {
            $validator->errors()->add('company_location_id', 'Địa điểm không thuộc công ty đã chọn.');
        }
    }
}
