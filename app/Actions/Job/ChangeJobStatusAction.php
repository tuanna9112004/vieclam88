<?php

namespace App\Actions\Job;

use App\Models\Job;
use App\Models\JobStatusHistory;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ChangeJobStatusAction
{
    /**
     * Toàn bộ 5 transition của Job transition matrix chính thức (docs/CORE-FLOWS.md mục 1.2).
     * Action này KHÔNG tự kiểm tra Job Publish Predicate (22 điều kiện, ADR-060) khi
     * `toStatus=published` — caller (`PublishJobAction`) bắt buộc phải validate predicate xong
     * trước khi gọi, Action chỉ ghi nhận transition + history.
     *
     * @var array<string, list<string>>
     */
    private const SUPPORTED_TRANSITIONS = [
        'draft' => ['published'],
        'published' => ['paused', 'closed'],
        'paused' => ['published', 'closed'],
    ];

    /**
     * Giả định: caller đã `lockForUpdate` và mở transaction bao quanh lời gọi này (nhất quán với
     * docs/CORE-FLOWS.md mục 1.1 — không tự mở transaction/lock riêng ở đây để tránh lock lồng
     * không cần thiết khi được gọi từ trong transaction khác, vd SaveJobVerificationAction).
     */
    public function handle(Job $job, string $toStatus, User $actor, ?string $reason = null): Job
    {
        $fromStatus = $job->status;

        if (! in_array($toStatus, self::SUPPORTED_TRANSITIONS[$fromStatus] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Không thể chuyển Job từ {$fromStatus} sang {$toStatus}.",
            ]);
        }

        if ($toStatus === 'closed' && trim((string) $reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'Cần lý do khi đóng Job.',
            ]);
        }

        $job->status = $toStatus;
        if ($toStatus === 'closed') {
            $job->closed_at = now();
        }
        if ($toStatus === 'published') {
            $job->published_at = now();
        }
        $job->save();

        JobStatusHistory::create([
            'job_id' => $job->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'changed_by' => $actor->id,
        ]);

        return $job;
    }
}
