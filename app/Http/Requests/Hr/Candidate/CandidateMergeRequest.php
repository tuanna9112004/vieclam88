<?php

namespace App\Http\Requests\Hr\Candidate;

use App\Models\Application;
use App\Models\Candidate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CandidateMergeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Candidate $candidate */
        $candidate = $this->route('candidate');

        return $this->user()->can('merge', $candidate);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Tu-merge/cycle/self-target da duoc MergeCandidateAction kiem tra day du trong
            // transaction (docs/CORE-FLOWS.md muc 6.3) — o day chi kiem tra hinh dang dau vao.
            'target_candidate_id' => ['required', 'integer', Rule::exists(Candidate::class, 'id')],
            'reason' => ['required', 'string', 'max:255'],
            'kept_application_id' => ['nullable', 'integer', Rule::exists(Application::class, 'id')],
        ];
    }
}
