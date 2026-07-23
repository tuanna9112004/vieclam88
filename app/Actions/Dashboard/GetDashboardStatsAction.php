<?php

namespace App\Actions\Dashboard;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Support\JobVerificationWarning;
use Illuminate\Support\Facades\DB;

/**
 * docs/CORE-FLOWS.md mục 9.1, ACCEPTANCE-CRITERIA.md mục 12 — Thống kê KPI Dashboard Phase 1.
 * Đảm bảo Branch Isolation: Branch Admin/Staff chỉ xem cơ sở mình;
 * Super Admin chọn cơ sở tùy chọn hoặc xem tất cả.
 * Thực hiện thống kê bằng các câu lệnh SQL Aggregate tập trung (tránh N+1).
 */
class GetDashboardStatsAction
{
    /**
     * @return array<string, int>
     */
    public function handle(User $user, ?int $branchId = null): array
    {
        $targetBranchId = $user->isSuperAdmin() ? $branchId : $user->branch_id;
        $today = now()->toDateString();

        $appQuery = Application::query();
        if ($targetBranchId !== null) {
            $appQuery->where('owner_branch_id', $targetBranchId);
        }

        // 1. Phân nhóm số lượng theo từng Stage
        $stageCounts = (clone $appQuery)
            ->select('stage', DB::raw('count(*) as aggregate'))
            ->groupBy('stage')
            ->pluck('aggregate', 'stage')
            ->all();

        // 2. Hồ sơ mới hôm nay
        $newToday = (clone $appQuery)
            ->whereDate('created_at', $today)
            ->count();

        // 3. Hồ sơ chưa liên hệ (stage = new & không có contact attempt)
        $uncontacted = (clone $appQuery)
            ->uncontacted()
            ->count();

        // 4. Hồ sơ đang xử lý
        $processingStages = ['contacting', 'consulted', 'interview_scheduled', 'interviewed', 'waiting_start'];
        $processing = 0;
        foreach ($processingStages as $s) {
            $processing += (int) ($stageCounts[$s] ?? 0);
        }

        // 5. Lịch gọi lại hôm nay
        $callbacksToday = (clone $appQuery)
            ->hasScheduledAppointment('callback', $today)
            ->count();

        // 6. Lịch phỏng vấn hôm nay
        $interviewsToday = (clone $appQuery)
            ->hasScheduledAppointment('interview', $today)
            ->count();

        // 7. Chờ đi làm
        $waitingStart = (int) ($stageCounts['waiting_start'] ?? 0);

        // 8. Đã đi làm
        $started = (int) ($stageCounts['started'] ?? 0);

        // 9. Đã đóng
        $closed = (int) ($stageCounts['closed'] ?? 0);

        // 10. Việc làm cần xác nhận / xử lý: Draft (chưa từng publish) hoặc published đã qua hạn
        // cảnh báo (docs/CORE-FLOWS.md mục 1.3) — 2 nhóm tách biệt, không trộn "chưa verify" của
        // Draft với "stale" của published (JobVerificationWarning là nguồn logic duy nhất).
        $jobQuery = Job::query();
        if ($targetBranchId !== null) {
            $jobQuery->where('owner_branch_id', $targetBranchId);
        }
        $warningDays = JobVerificationWarning::thresholds()['warning'];
        $jobsNeedingVerification = $jobQuery->where(function ($q) use ($warningDays) {
            $q->where('status', 'draft')
                ->orWhere(fn ($q2) => $q2->publishedStale($warningDays));
        })->count();

        return [
            'new_today' => $newToday,
            'uncontacted' => $uncontacted,
            'processing' => $processing,
            'callbacks_today' => $callbacksToday,
            'interviews_today' => $interviewsToday,
            'waiting_start' => $waitingStart,
            'started' => $started,
            'closed' => $closed,
            'jobs_needing_verification' => $jobsNeedingVerification,
        ];
    }
}
