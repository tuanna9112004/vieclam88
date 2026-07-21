<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "* -> closed" (docs/CORE-FLOWS.md muc 5.1): dung chung cho ca 6 stage dang active. `started`
 * la trang thai cuoi, khong dong duoc; `closed` da la closed nen cung khong hop le (chi Reopen —
 * ReopenApplicationAction — moi doi duoc stage cua Application dang closed).
 */
class CloseApplicationAction
{
    /** @var array<int, string> */
    private const CLOSABLE_STAGES = [
        'new', 'contacting', 'consulted', 'interview_scheduled', 'interviewed', 'waiting_start',
    ];

    public function handle(Application $application, string $closeReason, User $actor): Application
    {
        return DB::transaction(function () use ($application, $closeReason, $actor) {
            /** @var Application $locked */
            $locked = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->stage, self::CLOSABLE_STAGES, true)) {
                throw ValidationException::withMessages([
                    'to_stage' => "Không thể đóng hồ sơ đang ở giai đoạn '{$locked->stage}'.",
                ]);
            }

            if (trim($closeReason) === '') {
                throw ValidationException::withMessages([
                    'close_reason' => 'Cần lý do khi đóng hồ sơ.',
                ]);
            }

            $fromStage = $locked->stage;

            $locked->update([
                'stage' => 'closed',
                'stage_changed_at' => now(),
                'close_reason' => $closeReason,
                'closed_at' => now(),
            ]);

            ApplicationStatusHistory::create([
                'application_id' => $locked->id,
                'from_stage' => $fromStage,
                'to_stage' => 'closed',
                'close_reason' => $closeReason,
                'workflow_cycle' => $locked->workflow_cycle,
                'changed_by' => $actor->id,
                'actor_type' => 'user',
            ]);

            return $locked;
        });
    }
}
