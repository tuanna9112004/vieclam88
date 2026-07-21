<?php

namespace App\Http\Requests\Hr\Job;

use App\Models\Job;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Job $job */
        $job = $this->route('job');

        return $this->user()->can('verify', $job);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'result' => ['required', Rule::in(['still_open', 'needs_review', 'paused', 'closed'])],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Job $job */
            $job = $this->route('job');
            $result = $this->input('result');

            // ADR-059: Job chua tung publish khong the "tam dung"/"dong" mot hoat dong chua tung
            // co.
            if ($job->status === 'draft' && in_array($result, ['paused', 'closed'], true)) {
                $validator->errors()->add('result', 'Job chưa xuất bản, không thể xác nhận tạm dừng/đóng.');

                return;
            }

            // Doc/CORE-FLOWS mục 1.2: job_status_histories.reason bắt buộc khi to_status=closed —
            // verify flow dùng note làm reason nên note bắt buộc đúng lúc verify dẫn tới đóng Job.
            $willClose = in_array($job->status, ['published', 'paused'], true) && $result === 'closed';

            if ($willClose && trim((string) $this->input('note')) === '') {
                $validator->errors()->add('note', 'Cần ghi lý do khi xác nhận đóng Job.');
            }
        });
    }
}
