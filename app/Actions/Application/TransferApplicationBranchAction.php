<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\Models\ApplicationBranchHistory;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * docs/CORE-FLOWS.md mục 6.1 — chuyển cơ sở ngoại lệ cho Application. Chỉ Admin thực hiện.
 */
class TransferApplicationBranchAction
{
    public function handle(Application $application, Branch $toBranch, User $actor, string $reason): Application
    {
        return DB::transaction(function () use ($application, $toBranch, $actor, $reason) {
            /** @var Application $lockedApp */
            $lockedApp = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('transferBranch', $lockedApp);

            /** @var Branch $lockedToBranch */
            $lockedToBranch = Branch::withTrashed()
                ->whereKey($toBranch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (trim($reason) === '') {
                throw ValidationException::withMessages([
                    'reason' => 'Cần lý do khi chuyển cơ sở phụ trách hồ sơ.',
                ]);
            }

            if ($lockedToBranch->trashed() || $lockedToBranch->status !== 'active') {
                throw ValidationException::withMessages([
                    'to_branch_id' => 'Cơ sở đích phải đang hoạt động.',
                ]);
            }

            $fromBranchId = $lockedApp->owner_branch_id;

            if ((int) $fromBranchId === (int) $lockedToBranch->id) {
                throw ValidationException::withMessages([
                    'to_branch_id' => 'Cơ sở đích phải khác cơ sở hiện tại của hồ sơ.',
                ]);
            }

            $lockedApp->owner_branch_id = $lockedToBranch->id;
            $lockedApp->save();

            ApplicationBranchHistory::create([
                'application_id' => $lockedApp->id,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $lockedToBranch->id,
                'transferred_by' => $actor->id,
                'reason' => $reason,
            ]);

            return $lockedApp;
        });
    }
}
