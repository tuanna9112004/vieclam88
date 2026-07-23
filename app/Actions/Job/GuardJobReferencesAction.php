<?php

namespace App\Actions\Job;

use App\Enums\CompanyContactStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyContact;
use App\Models\CompanyLocation;
use App\Models\Job;
use Illuminate\Validation\ValidationException;

class GuardJobReferencesAction
{
    /**
     * Caller phải mở transaction trước khi gọi để các lock liên quan cùng thuộc một boundary.
     */
    public function handle(Job $job): void
    {
        $company = Company::query()->whereKey($job->company_id)->lockForUpdate()->first();

        if (! $company) {
            throw ValidationException::withMessages([
                'job' => 'Không thể thao tác vì công ty của Job đã bị xóa.',
            ]);
        }

        $branch = Branch::query()
            ->whereKey($job->owner_branch_id)
            ->where('status', 'active')
            ->lockForUpdate()
            ->first();

        if (! $branch) {
            throw ValidationException::withMessages([
                'job' => 'Không thể thao tác vì cơ sở phụ trách đã ngừng hoạt động hoặc bị xóa.',
            ]);
        }

        if ($job->company_contact_id !== null) {
            $contact = CompanyContact::query()
                ->whereKey($job->company_contact_id)
                ->where('company_id', $job->company_id)
                ->where('status', CompanyContactStatus::Active)
                ->lockForUpdate()
                ->first();

            if (! $contact) {
                throw ValidationException::withMessages([
                    'job' => 'Đầu mối liên hệ của Job không còn hợp lệ.',
                ]);
            }
        }

        $locationIds = $job->jobLocations()
            ->lockForUpdate()
            ->pluck('company_location_id')
            ->unique()
            ->values();

        if ($locationIds->isEmpty()) {
            return;
        }

        $validLocationCount = CompanyLocation::query()
            ->whereIn('id', $locationIds)
            ->where('company_id', $job->company_id)
            ->lockForUpdate()
            ->count();

        if ($validLocationCount !== $locationIds->count()) {
            throw ValidationException::withMessages([
                'job' => 'Một hoặc nhiều địa điểm của Job đã bị xóa hoặc không còn thuộc đúng công ty.',
            ]);
        }
    }
}
