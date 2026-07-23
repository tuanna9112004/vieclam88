<?php

namespace App\Actions\Job;

use App\Enums\CompanyContactStatus;
use App\Models\Job;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Job Publish Predicate chính thức — 22 điều kiện (docs/CORE-FLOWS.md mục 1.2, ADR-060).
 * Điều kiện 1-2 (Job tồn tại, chưa `deleted_at`) do route model binding + SoftDeletes global
 * scope đảm nhiệm (Job trashed không bao giờ tới được Action này). Điều kiện 6 (`owner_branch_id`
 * khác null) luôn đúng vì cột NOT NULL từ lúc tạo (ADR-046) — không kiểm tra lại. Điều kiện 22
 * (authorization) được `PublishJobRequest` chặn sớm và Action tái xác nhận bằng
 * `JobPolicy::publish()` sau khi khóa Job, trước 21 điều kiện dữ liệu (các lỗi dữ liệu trả 422).
 *
 * Salary Predicate (điều kiện 19) theo `docs/CORE-FLOWS.md` mục 1.2 + `docs/ACCEPTANCE-CRITERIA.md`
 * (2/3 nguồn đồng thuận — `docs/decisions/company-and-job-domain.md` ADR-060 ghi khác, tài liệu
 * đó cần sửa lại cho khớp, không phải code này): đúng MỘT trong hai mode loại trừ nhau —
 * `negotiable` bắt buộc mọi cột lương số đều `NULL`; ngược lại bắt buộc có ít nhất 1 số lương
 * dương hoặc mô tả lương thực. `salary_min <= salary_max` đã là DB CHECK constraint riêng
 * (`chk_jobs_salary_range`), không lặp lại kiểm tra ở đây vì dữ liệu invalid không thể tồn tại.
 *
 * Điều kiện bổ sung ngoài 22 điều của CORE-FLOWS.md mục 1.2 nhưng bắt buộc theo
 * `docs/ACCEPTANCE-CRITERIA.md` (mục 1.2, dòng "Company A gắn company_contact_id của Company
 * B..."): nếu `jobs.company_contact_id` khác null, contact đó phải active, chưa xóa và thuộc
 * đúng company của Job — contact có thể hợp lệ lúc store/update nhưng bị vô hiệu hóa sau đó, nên
 * phải tái xác nhận lúc publish, không chỉ tin dữ liệu đã lưu.
 */
class PublishJobAction
{
    public function handle(Job $job, User $actor, ?string $overrideReason = null): Job
    {
        return DB::transaction(function () use ($job, $actor, $overrideReason) {
            /** @var Job $lockedJob */
            $lockedJob = Job::whereKey($job->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('publish', $lockedJob);

            $usedOverride = $this->assertPredicate($lockedJob, $actor, $overrideReason);

            $reason = $usedOverride ? $overrideReason : null;

            return app(ChangeJobStatusAction::class)->handle($lockedJob, 'published', $actor, $reason);
        });
    }

    /**
     * @return bool true nếu Admin override PUB-VERIFY được dùng (để caller biết ghi reason vào history)
     */
    protected function assertPredicate(Job $job, User $actor, ?string $overrideReason): bool
    {
        $errors = [];

        // Dieu kien 3
        if (! in_array($job->status, ['draft', 'paused'], true)) {
            $errors['status'] = ['Job phải ở trạng thái nháp hoặc tạm dừng để xuất bản.'];
        }

        // Dieu kien 4-5
        $company = $job->company;
        if (! $company || $company->trashed() || $company->status !== 'active') {
            $errors['company'] = ['Công ty của Job phải đang hoạt động.'];
        }

        // Dieu kien 7-8
        $branch = $job->ownerBranch;
        if (! $branch || $branch->trashed() || $branch->status !== 'active') {
            $errors['owner_branch_id'] = ['Cơ sở phụ trách phải đang hoạt động.'];
        }

        // Dieu kien 9 - PUB-BRANCH-CTA
        if ($branch && empty($branch->phone) && empty($branch->zalo)) {
            $errors['owner_branch_cta'] = ['Cơ sở phụ trách cần có số điện thoại hoặc Zalo.'];
        }

        // Dieu kien 10-13
        foreach ([
            'title' => 'Tên vị trí',
            'job_description' => 'Mô tả công việc',
            'requirements' => 'Yêu cầu công việc',
            'benefits' => 'Quyền lợi',
        ] as $field => $label) {
            if (trim((string) $job->{$field}) === '') {
                $errors[$field] = ["{$label} không được để trống."];
            }
        }

        // Dieu kien 14-18
        $this->assertLocationPredicate($job, $errors);

        // Ngoai 22 dieu (ACCEPTANCE-CRITERIA.md) - contact phai con hop le luc publish
        $this->assertContactPredicate($job, $errors);

        // Dieu kien 19 - PUB-SALARY (ADR-060: it nhat 1/4, khong loai tru nhau)
        if (! $this->salaryPredicatePasses($job)) {
            $errors['salary'] = ['Cần có ít nhất một thông tin lương hợp lệ trước khi xuất bản.'];
        }

        // Dieu kien 20 - PUB-SHIFT
        if ($job->jobWorkShifts()->count() < 1) {
            $errors['job_work_shifts'] = ['Cần ít nhất 1 ca làm việc trước khi xuất bản.'];
        }

        // Dieu kien 21 - PUB-VERIFY
        $usedOverride = $this->assertVerificationPredicate($job, $actor, $overrideReason, $errors);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $usedOverride;
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    protected function assertLocationPredicate(Job $job, array &$errors): void
    {
        $primaryLocation = $job->jobLocations()
            ->where('is_primary', true)
            ->with('companyLocation.industrialPark')
            ->first();

        if (! $primaryLocation || ! $primaryLocation->companyLocation) {
            $errors['company_location_id'] = ['Cần chọn đúng 1 địa điểm chính cho Job.'];

            return;
        }

        $location = $primaryLocation->companyLocation;

        if ((int) $location->company_id !== (int) $job->company_id) {
            $errors['company_location_id'] = ['Địa điểm chính phải thuộc đúng công ty của Job.'];

            return;
        }

        if ($location->trashed() || $location->status !== 'active') {
            $errors['company_location_id'] = ['Địa điểm chính phải đang hoạt động.'];

            return;
        }

        $hasAdministrativeUnit = $location->administrative_unit_id !== null;
        $hasAddressDetail = trim((string) $location->address_detail) !== '';

        if (! $hasAdministrativeUnit && ! $hasAddressDetail) {
            $errors['location_clear'] = ['Địa điểm chính cần có tỉnh/thành hoặc địa chỉ chi tiết.'];
        }

        if ($location->industrial_park_id) {
            $park = $location->industrialPark;
            $unitMatches = $park && (int) $park->administrative_unit_id === (int) $location->administrative_unit_id;

            if (! $park || ! $park->is_active || ! $unitMatches) {
                $errors['industrial_park'] = ['Khu công nghiệp của địa điểm chính không hợp lệ.'];
            }
        }
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    protected function assertContactPredicate(Job $job, array &$errors): void
    {
        if (! $job->company_contact_id) {
            return;
        }

        $contact = $job->companyContact;

        $valid = $contact
            && ! $contact->trashed()
            && $contact->status === CompanyContactStatus::Active
            && (int) $contact->company_id === (int) $job->company_id;

        if (! $valid) {
            $errors['company_contact_id'] = ['Đầu mối liên hệ không hợp lệ — cần đang hoạt động, chưa xóa và thuộc đúng công ty.'];
        }
    }

    protected function salaryPredicatePasses(Job $job): bool
    {
        if ($job->salary_period === 'negotiable') {
            return $job->salary_min === null && $job->salary_max === null && $job->salary_base === null;
        }

        $hasPositiveAmount = ($job->salary_min !== null && $job->salary_min > 0)
            || ($job->salary_max !== null && $job->salary_max > 0)
            || ($job->salary_base !== null && $job->salary_base > 0);

        return $hasPositiveAmount || trim((string) $job->salary_description) !== '';
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    protected function assertVerificationPredicate(Job $job, User $actor, ?string $overrideReason, array &$errors): bool
    {
        $latest = $job->jobVerifications()->orderByDesc('verified_at')->orderByDesc('id')->first();

        $valid = $latest && $latest->result === 'still_open' && $this->verificationStillFresh($latest->verified_at);

        if ($valid) {
            return false;
        }

        if ($actor->isSuperAdmin() && trim((string) $overrideReason) !== '') {
            return true;
        }

        $errors['verification'] = [$actor->isSuperAdmin()
            ? 'Cần nhập lý do bỏ qua điều kiện xác minh còn tuyển.'
            : 'Job cần có xác nhận còn tuyển (still_open) gần nhất trước khi xuất bản.'];

        return false;
    }

    protected function verificationStillFresh(Carbon $verifiedAt): bool
    {
        $validDays = Setting::where('key', 'job_verification_valid_days')->value('value');

        if ($validDays === null || $validDays === '') {
            return true;
        }

        return $verifiedAt->gte(now()->subDays((int) $validDays));
    }
}
