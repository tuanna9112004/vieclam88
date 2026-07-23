<?php

namespace App\Actions\Job;

use App\Models\Job;
use App\Models\JobVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SaveJobVerificationAction
{
    /**
     * Ma trận chính thức Job Status × Verification Result (docs/CORE-FLOWS.md mục 1.3.1,
     * ADR-059) — authorization được FormRequest chặn sớm và tái xác nhận trên Job đã khóa trước
     * khi ghi verification hay đổi status.
     *
     * @param  array{result: string, note?: ?string}  $data
     */
    public function handle(Job $job, array $data, User $actor): JobVerification
    {
        return DB::transaction(function () use ($job, $data, $actor) {
            /** @var Job $lockedJob */
            $lockedJob = Job::whereKey($job->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('verify', $lockedJob);

            $verification = JobVerification::create([
                'job_id' => $lockedJob->id,
                'verified_by' => $actor->id,
                'result' => $data['result'],
                'note' => $data['note'] ?? null,
                'verified_at' => now(),
            ]);

            $lockedJob->last_checked_at = now();
            if ($data['result'] === 'still_open') {
                $lockedJob->last_verified_at = now();
            }
            $lockedJob->save();

            $toStatus = $this->resolveStatusTransition($lockedJob->status, $data['result']);

            if ($toStatus) {
                app(ChangeJobStatusAction::class)->handle($lockedJob, $toStatus, $actor, $data['note'] ?? null);
            }

            return $verification;
        });
    }

    protected function resolveStatusTransition(string $currentStatus, string $result): ?string
    {
        return match (true) {
            $currentStatus === 'published' && $result === 'paused' => 'paused',
            $currentStatus === 'published' && $result === 'closed' => 'closed',
            $currentStatus === 'paused' && $result === 'closed' => 'closed',
            default => null,
        };
    }
}
