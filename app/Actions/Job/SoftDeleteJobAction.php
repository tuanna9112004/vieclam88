<?php

namespace App\Actions\Job;

use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SoftDeleteJobAction
{
    public function handle(Job $job, User $actor): void
    {
        DB::transaction(function () use ($job, $actor) {
            $lockedJob = Job::query()->whereKey($job->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedJob->applications()->exists()) {
                throw ValidationException::withMessages([
                    'job' => 'Không thể xóa Job đã có hồ sơ ứng tuyển.',
                ]);
            }

            if (! in_array($lockedJob->status, ['draft', 'closed'], true)) {
                throw ValidationException::withMessages([
                    'job' => 'Chỉ có thể xóa Job nháp hoặc Job đã đóng.',
                ]);
            }

            $lockedJob->forceFill([
                'deleted_by' => $actor->id,
                'updated_by' => $actor->id,
            ])->save();
            $lockedJob->delete();
        });
    }
}
