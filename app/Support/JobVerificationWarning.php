<?php

namespace App\Support;

use App\Models\Job;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Nguon logic duy nhat cho canh bao "Job can xac minh lai" (docs/CORE-FLOWS.md muc 1.3,
 * docs/ACCEPTANCE-CRITERIA.md muc 1.6, ADR-042/048). Dung chung cho danh sach Job
 * (badge tung dong, khong query them — xem level()) va Dashboard (dem so luong qua
 * Job::scopePublishedStale) — tranh 2 noi tinh khac cong thuc nhau.
 *
 * Chi ap dung cho Job dang `published`: moc tham chieu la `last_verified_at`, hoac
 * `published_at` neu chua tung co lan xac nhan `still_open` nao (result needs_review/paused/
 * closed khong lam thay doi last_verified_at — nguon su that la cot nay, khong phai
 * last_checked_at). Draft/paused/closed luon tra ve null — khong trong duoc phan loai
 * "published stale" hay bi tron lan voi Draft "chua tung publish".
 */
class JobVerificationWarning
{
    public const int DEFAULT_WARNING_DAYS = 7;

    public const int DEFAULT_CRITICAL_DAYS = 14;

    /**
     * Doc ca 2 nguong trong 1 query duy nhat (tranh N+1 khi tinh level() cho nhieu Job trong 1
     * vong lap — goi 1 lan, truyen ket qua vao level()/scope tuong ung).
     *
     * @return array{warning: int, critical: int}
     */
    public static function thresholds(): array
    {
        $rows = Setting::query()
            ->whereIn('key', ['job_verification_warning_days', 'job_auto_pause_days'])
            ->pluck('value', 'key');

        return [
            'warning' => (int) ($rows['job_verification_warning_days'] ?? self::DEFAULT_WARNING_DAYS),
            'critical' => (int) ($rows['job_auto_pause_days'] ?? self::DEFAULT_CRITICAL_DAYS),
        ];
    }

    public static function referenceTime(Job $job): ?Carbon
    {
        return $job->last_verified_at ?? $job->published_at;
    }

    /**
     * Muc canh bao cua 1 Job da load san — thuan tinh toan tren thuoc tinh da co, khong query
     * them. Truyen $thresholds tu thresholds() de tai su dung khi lap qua nhieu Job.
     *
     * @param  array{warning: int, critical: int}  $thresholds
     * @return 'critical'|'warning'|null
     */
    public static function level(Job $job, array $thresholds): ?string
    {
        if ($job->status !== 'published') {
            return null;
        }

        $reference = self::referenceTime($job);
        if ($reference === null) {
            return null;
        }

        if ($reference->lt(now()->subDays($thresholds['critical']))) {
            return 'critical';
        }

        if ($reference->lt(now()->subDays($thresholds['warning']))) {
            return 'warning';
        }

        return null;
    }
}
