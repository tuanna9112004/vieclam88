<?php

namespace App\Actions\Job;

use App\Models\CompanyLocation;
use App\Models\Job;
use App\Models\JobLocation;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveJobDraftAction
{
    /**
     * Job Draft Contract (docs/CORE-FLOWS.md mục 1.0, ADR-046): chỉ title/company_id/
     * owner_branch_id/created_by bắt buộc — mọi thông tin khác (mô tả, lương, ca, xác minh...)
     * được phép thiếu ở draft, bổ sung dần trước khi publish (ngoài phạm vi lượt này).
     * `company_location_id` tùy chọn (Location cũng được phép thiếu ở draft) — nếu có, tái xác
     * nhận thuộc đúng company đã chọn ngay tại đây, không chỉ tin FormRequest.
     *
     * @param  array{title: string, company_id: int, owner_branch_id?: int, company_location_id?: ?int}  $data
     */
    public function handle(array $data, User $actor, ?Job $job = null): Job
    {
        $companyLocationId = $data['company_location_id'] ?? null;
        unset($data['company_location_id']);

        $data['slug'] = $this->uniqueSlug($data['title'], $job?->id);

        if ($job) {
            // hr.jobs.update không được sửa owner_branch_id (docs/CORE-FLOWS.md mục 1.1) — bỏ
            // qua dù client có gửi, không tin field này từ input.
            unset($data['owner_branch_id']);
            $data['updated_by'] = $actor->id;
            $job->update($data);
        } else {
            // Staff tự động gán owner_branch_id = branch của mình, không đọc từ input; Admin
            // bắt buộc chọn tường minh (đã ép ở StoreJobRequest).
            $data['owner_branch_id'] = $actor->isAdmin() ? $data['owner_branch_id'] : $actor->branch_id;
            $data['status'] = 'draft';
            $data['created_by'] = $actor->id;
            $data['public_id'] = (string) Str::ulid();
            $data['code'] = $this->uniqueCode();
            $job = Job::create($data);
        }

        $this->syncPrimaryLocation($job, $companyLocationId, $data['company_id']);

        return $job;
    }

    protected function syncPrimaryLocation(Job $job, ?int $companyLocationId, int $companyId): void
    {
        if (! $companyLocationId) {
            JobLocation::where('job_id', $job->id)->delete();

            return;
        }

        $location = CompanyLocation::where('id', $companyLocationId)->where('company_id', $companyId)->first();

        if (! $location) {
            throw ValidationException::withMessages([
                'company_location_id' => 'Địa điểm không thuộc công ty đã chọn.',
            ]);
        }

        JobLocation::where('job_id', $job->id)->where('company_location_id', '!=', $companyLocationId)->delete();

        JobLocation::updateOrCreate(
            ['job_id' => $job->id, 'company_location_id' => $companyLocationId],
            ['is_primary' => true]
        );
    }

    protected function uniqueSlug(string $title, ?int $ignoreId): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $suffix = 2;

        while (
            Job::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    protected function uniqueCode(): string
    {
        do {
            $code = 'JOB-'.Str::upper(Str::random(6));
        } while (Job::withTrashed()->where('code', $code)->exists());

        return $code;
    }
}
