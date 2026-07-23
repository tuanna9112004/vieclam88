<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Reopen Application contract chinh thuc (docs/CORE-FLOWS.md muc 5.5, `closed -> new`). 8 dieu
 * kien bat buoc, tat ca kiem tra trong 1 transaction voi lockForUpdate tren Application truoc
 * khi kiem tra. Action tai xac nhan ApplicationPolicy::changeStage tren ban ghi da khoa de
 * quyen cu khong song sot neu Application vua bi chuyen co so; dieu kien "Job khong con mo thi
 * chi Admin" (dieu kien 6) la predicate phu thuoc du lieu.
 */
class ReopenApplicationAction
{
    public function handle(Application $application, string $reason, User $actor): Application
    {
        return DB::transaction(function () use ($application, $reason, $actor) {
            /** @var Application $locked */
            $locked = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('changeStage', $locked);

            if ($locked->stage !== 'closed') {
                throw ValidationException::withMessages([
                    'to_stage' => "Chỉ mở lại được hồ sơ đang ở giai đoạn 'closed'.",
                ]);
            }

            if (trim($reason) === '') {
                throw ValidationException::withMessages([
                    'note' => 'Cần lý do khi mở lại hồ sơ.',
                ]);
            }

            // Dieu kien 4: closed vi trung (case C hoac merge) khong bao gio duoc mo lai.
            if ($locked->close_reason === 'duplicate') {
                throw ValidationException::withMessages([
                    'to_stage' => 'Hồ sơ đóng do trùng (duplicate) không thể mở lại.',
                ]);
            }

            // Dieu kien 3: Candidate chua deleted_at, status khong phai anonymized/merged.
            $candidate = Candidate::withTrashed()->findOrFail($locked->candidate_id);
            if ($candidate->trashed() || in_array($candidate->status, ['anonymized', 'merged'], true)) {
                throw ValidationException::withMessages([
                    'to_stage' => 'Không thể mở lại hồ sơ của Candidate đã ẩn danh, đã gộp, hoặc đã xóa.',
                ]);
            }

            // Dieu kien 5+6: Job chua deleted_at; neu Job khong con mo nhan ho so thi chi Admin.
            $job = Job::withTrashed()->findOrFail($locked->job_id);
            if ($job->trashed()) {
                throw ValidationException::withMessages([
                    'to_stage' => 'Không thể mở lại hồ sơ của Job đã xóa.',
                ]);
            }

            if (! $job->isOpenForApplication() && ! $actor->isSuperAdmin()) {
                throw ValidationException::withMessages([
                    'to_stage' => 'Job không còn nhận hồ sơ — chỉ Admin mới mở lại được.',
                ]);
            }

            $newCycle = $locked->workflow_cycle + 1;

            $locked->update([
                'stage' => 'new',
                'stage_changed_at' => now(),
                'close_reason' => null,
                'closed_at' => null,
                'expected_start_at' => null,
                'workflow_cycle' => $newCycle,
                'workflow_cycle_started_at' => now(),
                'reopened_at' => now(),
                'reopened_by' => $actor->id,
            ]);

            ApplicationStatusHistory::create([
                'application_id' => $locked->id,
                'from_stage' => 'closed',
                'to_stage' => 'new',
                'workflow_cycle' => $newCycle,
                'changed_by' => $actor->id,
                'actor_type' => 'user',
                'note' => $reason,
            ]);

            return $locked;
        });
    }
}
