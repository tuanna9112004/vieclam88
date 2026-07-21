<?php

namespace App\Http\Requests\Hr\Job;

use App\Enums\CompanyContactStatus;
use App\Models\Company;
use App\Models\CompanyContact;
use App\Models\CompanyLocation;
use App\Models\Job;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Job $job */
        $job = $this->route('job');

        return $this->user()->can('update', $job);
    }

    /**
     * hr.jobs.update không được phép sửa owner_branch_id dưới bất kỳ hình thức nào
     * (docs/ROUTE-MAP.md, docs/CORE-FLOWS.md mục 1.1) — chỉ hr.jobs.store (lần đầu) và
     * hr.jobs.transfer-branch (chưa xây, admin) được ghi cột này. Không có rule cho
     * owner_branch_id ở đây nên input này luôn bị bỏ qua dù client có gửi lên.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'company_id' => ['required', Rule::exists(Company::class, 'id')->withoutTrashed()],
            'company_location_id' => ['nullable', Rule::exists(CompanyLocation::class, 'id')->withoutTrashed()],
            'company_contact_id' => ['nullable', Rule::exists(CompanyContact::class, 'id')->withoutTrashed()],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->guardLocationBelongsToCompany($validator);
            $this->guardContactBelongsToCompany($validator);
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

    protected function guardContactBelongsToCompany(Validator $validator): void
    {
        $contactId = $this->input('company_contact_id');

        if (empty($contactId)) {
            return;
        }

        $contact = CompanyContact::find($contactId);

        if (! $contact || (int) $contact->company_id !== (int) $this->input('company_id')) {
            $validator->errors()->add('company_contact_id', 'Đầu mối không thuộc công ty đã chọn.');

            return;
        }

        if ($contact->status !== CompanyContactStatus::Active) {
            $validator->errors()->add('company_contact_id', 'Đầu mối đã ngừng hoạt động.');
        }
    }
}
