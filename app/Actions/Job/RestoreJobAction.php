<?php

namespace App\Actions\Job;

use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RestoreJobAction
{
    public function __construct(
        protected GuardJobReferencesAction $guardReferences
    ) {}

    public function handle(Job $job, User $actor): Job
    {
        return DB::transaction(function () use ($job, $actor) {
            $lockedJob = Job::withTrashed()
                ->whereKey($job->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            Gate::forUser($actor)->authorize('restore', $lockedJob);

            if (! $lockedJob->trashed()) {
                throw ValidationException::withMessages([
                    'job' => 'Job này chưa bị xóa.',
                ]);
            }

            if (! in_array($lockedJob->status, ['draft', 'closed'], true)) {
                throw ValidationException::withMessages([
                    'job' => 'Không thể khôi phục Job ở trạng thái chưa đóng.',
                ]);
            }

            $this->guardReferences->handle($lockedJob);

            $lockedJob->restore();
            $lockedJob->forceFill(['updated_by' => $actor->id])->save();

            return $lockedJob;
        });
    }
}
