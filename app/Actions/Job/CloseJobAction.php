<?php

namespace App\Actions\Job;

use App\Enums\JobCloseReason;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CloseJobAction
{
    /**
     * published|paused -> closed. `jobs.close_reason` va `job_status_histories.reason` dung
     * chung 1 gia tri (docs/DATABASE-DICTIONARY.md 9.26) — set close_reason truoc khi
     * ChangeJobStatusAction luu, truyen cung string lam history reason.
     */
    public function handle(Job $job, User $actor, JobCloseReason $closeReason): Job
    {
        return DB::transaction(function () use ($job, $actor, $closeReason) {
            /** @var Job $lockedJob */
            $lockedJob = Job::whereKey($job->id)->lockForUpdate()->firstOrFail();

            $lockedJob->close_reason = $closeReason;

            return app(ChangeJobStatusAction::class)->handle($lockedJob, 'closed', $actor, $closeReason->value);
        });
    }
}
