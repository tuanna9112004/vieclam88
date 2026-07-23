<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\Models\ApplicationContactAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * docs/CORE-FLOWS.md muc 4, 5.2, 5.4: ghi Contact Log — contacted_by/contacted_at/workflow_cycle
 * luon do server tinh, khong doc tu client. workflow_cycle duoc doc trong cung transaction voi
 * lockForUpdate tren Application de tranh race voi Reopen (ChangeApplicationStageAction) dang
 * tang workflow_cycle dong thoi. Ghi Contact Log khong bao gio doi applications.stage (ADR-009).
 */
class RecordContactAttemptAction
{
    /**
     * @param  array{channel: string, result: string, note: ?string}  $data
     */
    public function handle(Application $application, array $data, User $actor): ApplicationContactAttempt
    {
        return DB::transaction(function () use ($application, $data, $actor) {
            /** @var Application $lockedApplication */
            $lockedApplication = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('recordContact', $lockedApplication);

            return ApplicationContactAttempt::create([
                'application_id' => $lockedApplication->id,
                'contacted_by' => $actor->id,
                'channel' => $data['channel'],
                'result' => $data['result'],
                'workflow_cycle' => $lockedApplication->workflow_cycle,
                'contacted_at' => now(),
                'note' => $data['note'] ?? null,
            ]);
        });
    }
}
