<?php

namespace App\Actions\Dashboard;

use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * docs/CORE-FLOWS.md mục 9.1 — Dashboard Admin toàn hệ thống.
 * Hỗ trợ bộ lọc khoảng ngày (date_from, date_to), cơ sở (owner_branch_id), công ty (company_id) và việc làm (job_id).
 * Tính toán tỷ lệ chuyển đổi (Application -> started), thống kê số lượng theo Job & Company.
 * Không bị sai đếm khi chuyển cơ sở (Transfer), gộp ứng viên (Merge) hay đóng trùng (Duplicate).
 */
class GetAdminDashboardStatsAction
{
    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function handle(User $actor, array $filters = []): array
    {
        $today = now()->toDateString();

        $appQuery = Application::query();
        $jobQuery = Job::query();

        // 1. Áp dụng bộ lọc Branch Isolation / Admin Selected Branches
        if ($actor->isStaff()) {
            $appQuery->where('owner_branch_id', $actor->branch_id);
            $jobQuery->where('owner_branch_id', $actor->branch_id);
        } elseif (! empty($filters['owner_branch_id'])) {
            $branchIds = (array) $filters['owner_branch_id'];
            $appQuery->whereIn('owner_branch_id', $branchIds);
            $jobQuery->whereIn('owner_branch_id', $branchIds);
        }

        // 2. Bộ lọc Company
        if (! empty($filters['company_id'])) {
            $companyId = (int) $filters['company_id'];
            $appQuery->whereHas('job', fn ($q) => $q->where('company_id', $companyId));
            $jobQuery->where('company_id', $companyId);
        }

        // 3. Bộ lọc Job
        if (! empty($filters['job_id'])) {
            $jobId = (int) $filters['job_id'];
            $appQuery->where('job_id', $jobId);
            $jobQuery->where('id', $jobId);
        }

        // 4. Bộ lọc Khoảng ngày (Date Range)
        if (! empty($filters['date_from'])) {
            $appQuery->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $appQuery->whereDate('created_at', '<=', $filters['date_to']);
        }

        // --- BẮT ĐẦU TÍNH TOÁN KPI METRICS ---

        // Tổng số hồ sơ
        $totalApplications = (clone $appQuery)->count();

        // Thống kê phân nhóm theo Stage
        $stageCounts = (clone $appQuery)
            ->select('stage', DB::raw('count(*) as aggregate'))
            ->groupBy('stage')
            ->pluck('aggregate', 'stage')
            ->all();

        $newToday = (clone $appQuery)
            ->whereDate('created_at', $today)
            ->count();

        $uncontacted = (clone $appQuery)
            ->uncontacted()
            ->count();

        $processingStages = ['contacting', 'consulted', 'interview_scheduled', 'interviewed', 'waiting_start'];
        $processing = 0;
        foreach ($processingStages as $s) {
            $processing += (int) ($stageCounts[$s] ?? 0);
        }

        $callbacksToday = (clone $appQuery)
            ->hasScheduledAppointment('callback', $today)
            ->count();

        $interviewsToday = (clone $appQuery)
            ->hasScheduledAppointment('interview', $today)
            ->count();

        $waitingStart = (int) ($stageCounts['waiting_start'] ?? 0);
        $started = (int) ($stageCounts['started'] ?? 0);
        $closed = (int) ($stageCounts['closed'] ?? 0);

        // Tỷ lệ chuyển đổi % (Application -> Started)
        $conversionRate = $totalApplications > 0 ? round(($started / $totalApplications) * 100, 2) : 0.0;

        // Thống kê theo Job (Top Jobs kèm số lượng Application)
        $topJobs = (clone $jobQuery)
            ->with(['company:id,name', 'ownerBranch:id,name'])
            ->withCount([
                'applications',
                'applications as started_applications_count' => fn ($q) => $q->where('stage', 'started'),
            ])
            ->orderByDesc('applications_count')
            ->limit(10)
            ->get();

        // Thống kê theo Company
        $companyQuery = Company::query();
        if (! empty($filters['company_id'])) {
            $companyQuery->where('id', $filters['company_id']);
        }

        $companiesStats = $companyQuery
            ->withCount([
                'jobs',
                'jobs as applications_count' => function ($q) use ($filters, $actor) {
                    if ($actor->isStaff()) {
                        $q->where('owner_branch_id', $actor->branch_id);
                    } elseif (! empty($filters['owner_branch_id'])) {
                        $q->whereIn('owner_branch_id', (array) $filters['owner_branch_id']);
                    }
                },
            ])
            ->orderByDesc('applications_count')
            ->limit(10)
            ->get();

        // Số việc làm cần xác nhận / xử lý
        $jobsNeedingVerification = (clone $jobQuery)
            ->where(function ($q) {
                $q->where('status', 'draft')
                  ->orWhereNull('last_verified_at');
            })->count();

        return [
            'total_applications' => $totalApplications,
            'new_today' => $newToday,
            'uncontacted' => $uncontacted,
            'processing' => $processing,
            'callbacks_today' => $callbacksToday,
            'interviews_today' => $interviewsToday,
            'waiting_start' => $waitingStart,
            'started' => $started,
            'closed' => $closed,
            'conversion_rate' => $conversionRate,
            'jobs_needing_verification' => $jobsNeedingVerification,
            'top_jobs' => $topJobs,
            'companies_stats' => $companiesStats,
        ];
    }
}
