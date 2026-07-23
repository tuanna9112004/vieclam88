<?php

namespace App\Actions\Job;

use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PauseJobAction
{
    /**
     * published -> paused: khong can Publish Predicate, chi can transition hop le
     * (ChangeJobStatusAction tu kiem tra) + authorization duoc tai xac nhan sau khi khoa Job.
     */
    public function handle(Job $job, User $actor, ?string $reason = null): Job
    {
        return DB::transaction(function () use ($job, $actor, $reason) {
            /** @var Job $lockedJob */
            $lockedJob = Job::whereKey($job->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('pause', $lockedJob);

            return app(ChangeJobStatusAction::class)->handle($lockedJob, 'paused', $actor, $reason);
        });
    }
}
