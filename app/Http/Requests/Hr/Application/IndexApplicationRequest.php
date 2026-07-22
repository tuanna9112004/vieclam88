<?php

namespace App\Http\Requests\Hr\Application;

use Illuminate\Foundation\Http\FormRequest;

class IndexApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:191'],
            'job_id' => ['nullable', 'integer', 'exists:jobs,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            // Chi Admin dung field nay (Staff bi ep owner_branch_id o Controller, khong doc tu
            // request) — docs/CORE-FLOWS.md muc 9.2.
            'owner_branch_id' => ['nullable', 'array'],
            'owner_branch_id.*' => ['integer', 'exists:branches,id'],
            'stage' => ['nullable', 'string', 'in:new,contacting,consulted,interview_scheduled,interviewed,waiting_start,started,closed'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'uncontacted' => ['nullable', 'in:1'],
            'processing' => ['nullable', 'in:1'],
            'has_callback' => ['nullable', 'in:1'],
            'callback_today' => ['nullable', 'in:1'],
            'has_interview' => ['nullable', 'in:1'],
            'interview_today' => ['nullable', 'in:1'],
            'needs_duplicate_review' => ['nullable', 'in:1'],
        ];
    }
}
