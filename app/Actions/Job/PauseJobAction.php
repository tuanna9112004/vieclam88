<?php

namespace App\Actions\Job;

use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PauseJobAction
{
    /**
     * published -> paused: khong can Publish Predicate, chi can transition hop le
     * (ChangeJobStatusAction tu kiem tra) + authorization (da qua JobPolicy/PauseJobRequest).
     */
    public function handle(Job $job, User $actor, ?string $reason = null): Job
    {
        return DB::transaction(function () use ($job, $actor, $reason) {
            /** @var Job $lockedJob */
            $lockedJob = Job::whereKey($job->id)->lockForUpdate()->firstOrFail();

            return app(ChangeJobStatusAction::class)->handle($lockedJob, 'paused', $actor, $reason);
        });
    }
}
