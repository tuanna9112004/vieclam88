<?php

namespace App\Http\Requests\Hr\Candidate;

use App\Models\Candidate;
use Illuminate\Foundation\Http\FormRequest;

class CandidateAnonymizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Candidate $candidate */
        $candidate = $this->route('candidate');

        return $this->user()->can('anonymize', $candidate);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Candidate $candidate */
        $candidate = $this->route('candidate');

        return [
            // Anonymize khong hoan tac duoc (docs/CORE-FLOWS.md muc 7.3) va schema candidates
            // khong co cot luu ly do rieng cho anonymize (khac merge_reason) — "confirmation
            // phrase" (nhap dung ho ten hien tai) la lop an toan chinh, chi validate, khong luu.
            'confirm_name' => [
                'required', 'string',
                function ($attribute, $value, $fail) use ($candidate) {
                    if (trim($value) !== $candidate->full_name) {
                        $fail('Vui lòng nhập chính xác họ tên ứng viên để xác nhận ẩn danh.');
                    }
                },
            ],
        ];
    }
}
