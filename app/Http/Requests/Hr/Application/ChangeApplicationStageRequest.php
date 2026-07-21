<?php

namespace App\Http\Requests\Hr\Application;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;

class ChangeApplicationStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Application $application */
        $application = $this->route('application');

        return $this->user()->can('changeStage', $application);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // 'new' chi hop le nhu dich cua Reopen (closed -> new, muc 5.5) — ChangeApplicationStageAction
            // tu kiem tra stage hien tai thuc su la 'closed' truoc khi cho phep.
            'to_stage' => [
                'required', 'string',
                'in:new,contacting,consulted,interview_scheduled,interviewed,waiting_start,started,closed',
            ],
            // 'duplicate' co trong DB enum nhung chi danh cho merge-conflict (Candidate Merge —
            // chua build), khong cho chon thu cong qua route nay.
            'close_reason' => [
                'nullable', 'string', 'required_if:to_stage,closed',
                'in:unreachable,candidate_cancelled,employer_cancelled,unsuitable,job_closed,other',
            ],
            'expected_start_at' => ['nullable', 'date', 'required_if:to_stage,waiting_start'],
            'started_at' => ['nullable', 'date', 'required_if:to_stage,started'],
            // Ly do mo lai (Reopen) — luu vao application_status_histories.note (muc 5.5 dieu
            // kien 2), tai dung cot co san.
            'note' => ['nullable', 'string', 'max:255', 'required_if:to_stage,new'],
        ];
    }
}
