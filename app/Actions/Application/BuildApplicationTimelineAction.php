<?php

namespace App\Actions\Application;

use App\Models\Application;
use Illuminate\Support\Collection;

/**
 * Timeline tong hop (docs/ACCEPTANCE-CRITERIA.md muc 3.1, docs/CORE-FLOWS.md muc 5.4): gop 5
 * bang lich su da co san (status history, contact attempt, appointment, note, branch history)
 * thanh 1 danh sach duy nhat sap theo thoi gian — khong tao bang/model timeline rieng, chi doc
 * lai du lieu da ton tai. Du lieu chu ky cu (workflow_cycle truoc) van hien thi day du, khong bi
 * loc/an — chi Blade dung workflow_cycle de nhom hien thi.
 */
class BuildApplicationTimelineAction
{
    /**
     * @return Collection<int, array{type: string, occurred_at: \Illuminate\Support\Carbon, workflow_cycle: ?int, actor: ?\App\Models\User, model: mixed}>
     */
    public function handle(Application $application): Collection
    {
        $application->loadMissing([
            'statusHistories.changedBy',
            'contactAttempts.contactedBy',
            'appointments.createdBy',
            'appointments.completedBy',
            'notes.user',
            'branchHistories.fromBranch',
            'branchHistories.toBranch',
            'branchHistories.transferredBy',
        ]);

        $entries = collect();

        foreach ($application->statusHistories as $history) {
            $entries->push([
                'type' => 'status_change',
                'occurred_at' => $history->created_at,
                'workflow_cycle' => $history->workflow_cycle,
                'actor' => $history->changedBy,
                'model' => $history,
            ]);
        }

        foreach ($application->contactAttempts as $attempt) {
            $entries->push([
                'type' => 'contact_attempt',
                'occurred_at' => $attempt->created_at,
                'workflow_cycle' => $attempt->workflow_cycle,
                'actor' => $attempt->contactedBy,
                'model' => $attempt,
            ]);
        }

        foreach ($application->appointments as $appointment) {
            $entries->push([
                'type' => 'appointment',
                'occurred_at' => $appointment->created_at,
                'workflow_cycle' => $appointment->workflow_cycle,
                'actor' => $appointment->createdBy,
                'model' => $appointment,
            ]);
        }

        // Note khong gan workflow_cycle (ghi chu lam viec, khong thuoc chu ky xu ly — muc 7.3.1).
        foreach ($application->notes as $note) {
            $entries->push([
                'type' => 'note',
                'occurred_at' => $note->created_at,
                'workflow_cycle' => null,
                'actor' => $note->user,
                'model' => $note,
            ]);
        }

        // Chuyen co so cung khong thuoc chu ky xu ly — no la thuoc tinh cua Application, khong
        // phai cua tung lan xu ly.
        foreach ($application->branchHistories as $branchHistory) {
            $entries->push([
                'type' => 'branch_transfer',
                'occurred_at' => $branchHistory->created_at,
                'workflow_cycle' => null,
                'actor' => $branchHistory->transferredBy,
                'model' => $branchHistory,
            ]);
        }

        return $entries->sortBy('occurred_at')->values();
    }
}
