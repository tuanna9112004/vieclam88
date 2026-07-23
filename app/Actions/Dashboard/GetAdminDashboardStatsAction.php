<?php

namespace App\Actions\Dashboard;

use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use App\Support\JobVerificationWarning;
use Illuminate\Support\Facades\DB;

/**
 * docs/CORE-FLOWS.md mục 9.1 — Dashboard Super Admin toàn hệ thống,
 * Branch Admin/Staff chỉ trong cơ sở được gán.
 * Hỗ trợ bộ lọc khoảng ngày (date_from, date_to), cơ sở (owner_branch_id), công ty (company_id) và việc làm (job_id).
 * Tính toán tỷ lệ chuyển đổi (Application -> started), thống kê số lượng theo Job & Company.
 * Không bị sai đếm khi chuyển cơ sở (Transfer), gộp ứng viên (Merge) hay đóng trùng (Duplicate).
 */
class GetAdminDashboardStatsAction
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function handle(User $actor, array $filters = []): array
    {
        $today = now()->toDateString();

        // 1. Branch Isolation / Super Admin Selected Branches
        $branchIds = null;
        if (! $actor->isSuperAdmin()) {
            $branchIds = [$actor->branch_id];
        } elseif (! empty($filters['owner_branch_id'])) {
            $branchIds = (array) $filters['owner_branch_id'];
        }

        $companyId = ! empty($filters['company_id']) ? (int) $filters['company_id'] : null;
        $jobId = ! empty($filters['job_id']) ? (int) $filters['job_id'] : null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        /**
         * Ap dung dung 1 bo dieu kien Application (branch/job/date) cho ca KPI, Top Jobs va
         * Companies — truoc day date_from/date_to chi ap dung cho KPI, lam Top Jobs/Companies
         * lech so voi KPI khi co filter ngay. company_id khong dua vao day vi da duoc scope
         * rieng: whereHas cho $appQuery, hoac tu nhien qua company/job dang duyet o duoi.
         * Cot phai qualify voi "applications." vi Company::applications() la hasManyThrough
         * (JOIN voi jobs) — cot khong qualify se bi loi ambiguous do jobs cung co owner_branch_id.
         */
        $applyApplicationFilters = function ($query) use ($branchIds, $jobId, $dateFrom, $dateTo) {
            if ($branchIds !== null) {
                $query->whereIn('applications.owner_branch_id', $branchIds);
            }
            if ($jobId !== null) {
                $query->where('applications.job_id', $jobId);
            }
            if ($dateFrom !== null) {
                $query->whereDate('applications.created_at', '>=', $dateFrom);
            }
            if ($dateTo !== null) {
                $query->whereDate('applications.created_at', '<=', $dateTo);
            }

            return $query;
        };

        $applyJobFilters = function ($query) use ($branchIds, $jobId) {
            if ($branchIds !== null) {
                $query->whereIn('jobs.owner_branch_id', $branchIds);
            }
            if ($jobId !== null) {
                $query->where('jobs.id', $jobId);
            }

            return $query;
        };

        $appQuery = $applyApplicationFilters(Application::query());
        $jobQuery = $applyJobFilters(Job::query());

        // 2. Bộ lọc Company
        if ($companyId !== null) {
            $appQuery->whereHas('job', fn ($q) => $q->where('company_id', $companyId));
            $jobQuery->where('company_id', $companyId);
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

        // Thống kê theo Job (Top Jobs kèm số lượng Application) — applications_count phải theo
        // dung bo loc branch/job/date nhu KPI, khong duoc dem het moi Application cua Job.
        $topJobs = (clone $jobQuery)
            ->with(['company:id,name', 'ownerBranch:id,name'])
            ->withCount([
                'applications' => fn ($q) => $applyApplicationFilters($q),
                'applications as started_applications_count' => fn ($q) => $applyApplicationFilters($q)->where('stage', 'started'),
            ])
            ->orderByDesc('applications_count')
            ->limit(10)
            ->get();

        // Thống kê theo Company: 'jobs' la so Job, 'applications' la so Ho so qua Job (Company
        // hasManyThrough Application) — khong dung lai relation 'jobs' lam alias cho ho so.
        $companyQuery = Company::query();
        if ($companyId !== null) {
            $companyQuery->where('id', $companyId);
        }

        $companiesStats = $companyQuery
            ->withCount([
                'jobs' => fn ($q) => $applyJobFilters($q),
                'applications' => fn ($q) => $applyApplicationFilters($q),
            ])
            ->orderByDesc('applications_count')
            ->limit(10)
            ->get();

        // Số việc làm cần xác nhận / xử lý: Draft (chưa từng publish) hoặc published đã qua hạn
        // cảnh báo (docs/CORE-FLOWS.md mục 1.3) — cùng logic dùng chung với GetDashboardStatsAction
        // và danh sách Job (JobVerificationWarning).
        $warningDays = JobVerificationWarning::thresholds()['warning'];
        $jobsNeedingVerification = (clone $jobQuery)
            ->where(function ($q) use ($warningDays) {
                $q->where('status', 'draft')
                    ->orWhere(fn ($q2) => $q2->publishedStale($warningDays));
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
