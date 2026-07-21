<?php

namespace App\Http\Requests\Public;

use App\Actions\Application\IssueSubmissionTokenAction;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{8,20}$/'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
            // ADR-070 (docs/DATABASE-DICTIONARY.md muc 9.4): don vi is_active=false khong duoc
            // chon cho du lieu moi — kiem tra o Form Request.
            'current_administrative_unit_id' => [
                'nullable', 'integer',
                Rule::exists('administrative_units', 'id')->where('is_active', true),
            ],
            'education_level' => ['nullable', 'string', 'max:100'],
            'experience_summary' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
            'submission_token' => ['required', 'string'],
            // Honeypot (.claude/rules/security.md): truong an voi nguoi dung, bot dien vao se bi tu choi.
            'website' => ['prohibited'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $job = $this->route('job');
            $token = $this->input('submission_token');
            $tokens = $this->session()->get(IssueSubmissionTokenAction::SESSION_KEY, []);

            $valid = is_string($token) && isset($tokens[$token]) && $job !== null
                && (int) $tokens[$token]['job_id'] === $job->id;

            if (! $valid) {
                $validator->errors()->add(
                    'submission_token',
                    'Phiên ứng tuyển đã hết hạn, vui lòng tải lại trang và thử lại.'
                );
            }
        });
    }
}
