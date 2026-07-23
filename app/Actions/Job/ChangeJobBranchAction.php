<?php

namespace App\Actions\Job;

use App\Models\Branch;
use App\Models\Job;
use App\Models\JobBranchHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * docs/CORE-FLOWS.md mục 1.1, ADR-054 — chỉ Admin (JobPolicy::transferBranch), chỉ khi Job
 * draft/paused (không published — phải pause trước qua ChangeJobStatusAction như bước riêng,
 * không gộp transaction; không closed — đợt tuyển đã kết thúc). Job đã soft-delete không tới
 * được đây (route model binding + SoftDeletes global scope).
 */
class ChangeJobBranchAction
{
    public function handle(Job $job, Branch $toBranch, User $actor, string $reason): Job
    {
        return DB::transaction(function () use ($job, $toBranch, $actor, $reason) {
            /** @var Job $lockedJob */
            $lockedJob = Job::whereKey($job->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('transferBranch', $lockedJob);

            /** @var Branch $lockedToBranch */
            $lockedToBranch = Branch::withTrashed()
                ->whereKey($toBranch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedJob->status, ['draft', 'paused'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ đổi cơ sở phụ trách khi Job đang nháp hoặc tạm dừng.',
                ]);
            }

            if (trim($reason) === '') {
                throw ValidationException::withMessages([
                    'reason' => 'Cần lý do khi đổi cơ sở phụ trách.',
                ]);
            }

            // Tai xac nhan Branch dich con hop le — khong chi tin FormRequest (rang buoc dua).
            if ($lockedToBranch->trashed() || $lockedToBranch->status !== 'active') {
                throw ValidationException::withMessages([
                    'to_branch_id' => 'Cơ sở đích phải đang hoạt động.',
                ]);
            }

            $fromBranchId = $lockedJob->owner_branch_id;

            if ((int) $fromBranchId === (int) $lockedToBranch->id) {
                throw ValidationException::withMessages([
                    'to_branch_id' => 'Cơ sở đích phải khác cơ sở hiện tại.',
                ]);
            }

            $lockedJob->owner_branch_id = $lockedToBranch->id;
            $lockedJob->save();

            JobBranchHistory::create([
                'job_id' => $lockedJob->id,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $lockedToBranch->id,
                'reason' => $reason,
                'changed_by' => $actor->id,
            ]);

            return $lockedJob;
        });
    }
}
