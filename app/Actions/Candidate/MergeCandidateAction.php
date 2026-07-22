<?php

namespace App\Actions\Candidate;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * docs/CORE-FLOWS.md mục 6.3 — Merge Candidate contract đầy đủ (bao gồm merged family & root resolution).
 * Chỉ Admin mới được thực hiện.
 */
class MergeCandidateAction
{
    /**
     * @param Candidate $sourceCandidate Candidate nguồn (sẽ bị status = 'merged', merged_into_candidate_id = target->id)
     * @param Candidate $targetCandidate Candidate đích (nhận dữ liệu)
     * @param User $actor Người thực hiện (Admin)
     * @param string $reason Lý do merge (bắt buộc)
     * @param int|null $keptApplicationId ID của Application được ưu tiên giữ khi có xung đột trùng job
     */
    public function handle(
        Candidate $sourceCandidate,
        Candidate $targetCandidate,
        User $actor,
        string $reason,
        ?int $keptApplicationId = null
    ): Candidate {
        // 1. Validation kiểm tra trước transaction
        if ((int) $sourceCandidate->id === (int) $targetCandidate->id) {
            throw ValidationException::withMessages([
                'source_candidate_id' => 'Không thể gộp ứng viên vào chính nó.',
            ]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'Cần lý do khi thực hiện gộp ứng viên.',
            ]);
        }

        if ($sourceCandidate->trashed() || $sourceCandidate->status === 'anonymized') {
            throw ValidationException::withMessages([
                'source_candidate_id' => 'Ứng viên nguồn đã bị xóa hoặc ẩn danh, không thể gộp.',
            ]);
        }

        if ($targetCandidate->trashed() || $targetCandidate->status === 'anonymized') {
            throw ValidationException::withMessages([
                'target_candidate_id' => 'Ứng viên đích đã bị xóa hoặc ẩn danh, không thể gộp.',
            ]);
        }

        if ($sourceCandidate->status === 'merged' || $sourceCandidate->merged_into_candidate_id !== null) {
            throw ValidationException::withMessages([
                'source_candidate_id' => 'Ứng viên nguồn đã được gộp trước đó.',
            ]);
        }

        // 2. Chống vòng lặp (Cycle prevention): đi từ targetCandidate theo merged_into_candidate_id
        $freshTarget = $targetCandidate->fresh() ?? $targetCandidate;
        $targetRoot = $freshTarget->resolveRoot();

        if ((int) $targetRoot->id === (int) $sourceCandidate->id) {
            throw ValidationException::withMessages([
                'target_candidate_id' => 'Phát hiện vòng lặp gộp ứng viên (cycle), không thể gộp.',
            ]);
        }

        return DB::transaction(function () use ($sourceCandidate, $targetCandidate, $actor, $reason, $keptApplicationId) {
            // Row lock cả source và target candidates
            /** @var Candidate $lockedSource */
            $lockedSource = Candidate::whereKey($sourceCandidate->id)->lockForUpdate()->firstOrFail();
            /** @var Candidate $lockedTarget */
            $lockedTarget = Candidate::whereKey($targetCandidate->id)->lockForUpdate()->firstOrFail();

            // Re-check inside lock
            if ($lockedSource->status === 'merged' || $lockedSource->merged_into_candidate_id !== null) {
                throw ValidationException::withMessages([
                    'source_candidate_id' => 'Ứng viên nguồn đã được gộp trước đó.',
                ]);
            }

            // Lock tất cả Applications của source và target
            $sourceAppIds = Application::where('candidate_id', $lockedSource->id)->pluck('id')->all();
            $targetAppIds = Application::where('candidate_id', $lockedTarget->id)->pluck('id')->all();
            $allAppIds = array_unique(array_merge($sourceAppIds, $targetAppIds));

            if (! empty($allAppIds)) {
                Application::whereIn('id', $allAppIds)->lockForUpdate()->get();
            }

            // Xử lý Applications của source
            $sourceApps = Application::where('candidate_id', $lockedSource->id)->get();

            foreach ($sourceApps as $sourceApp) {
                $targetFamilyIds = $lockedTarget->getMergedFamilyIds();
                $sameJobApps = Application::whereIn('candidate_id', $targetFamilyIds)
                    ->where('job_id', $sourceApp->job_id)
                    ->get();

                if ($sameJobApps->isEmpty()) {
                    // CASE A: Application KHÔNG trùng Job -> Đổi candidate_id của sourceApp thành targetCandidate->id
                    $sourceApp->update([
                        'candidate_id' => $lockedTarget->id,
                    ]);
                } else {
                    // CASE B: Application TRÙNG Job (Merge conflict)
                    // Cả 2 Application đều giữ nguyên candidate_id gốc của mình!
                    $targetSameJobApp = $sameJobApps->first();

                    $keepApp = null;
                    $closeApp = null;

                    if ($keptApplicationId !== null) {
                        if ((int) $sourceApp->id === (int) $keptApplicationId) {
                            $keepApp = $sourceApp;
                            $closeApp = $targetSameJobApp;
                        } else {
                            $keepApp = $targetSameJobApp;
                            $closeApp = $sourceApp;
                        }
                    } else {
                        // Tự chọn canonical Application nếu admin không chỉ định
                        $keepApp = $targetSameJobApp;
                        $closeApp = $sourceApp;
                    }

                    // Đóng closeApp với stage = 'closed', close_reason = 'duplicate'
                    if ($closeApp->stage !== 'closed') {
                        $fromStage = $closeApp->stage;
                        $closeApp->update([
                            'stage' => 'closed',
                            'close_reason' => 'duplicate',
                            'closed_at' => now(),
                            'stage_changed_at' => now(),
                        ]);

                        ApplicationStatusHistory::create([
                            'application_id' => $closeApp->id,
                            'from_stage' => $fromStage,
                            'to_stage' => 'closed',
                            'close_reason' => 'duplicate',
                            'workflow_cycle' => $closeApp->workflow_cycle,
                            'changed_by' => $actor->id,
                            'actor_type' => 'user',
                            'note' => 'Đóng tự động do gộp ứng viên trùng lặp',
                            'metadata' => [
                                'merge_kept_application_id' => $keepApp->id,
                                'merge_target_candidate_id' => $lockedTarget->id,
                            ],
                        ]);
                    }
                }
            }

            // Cập nhật trạng thái của Candidate Nguồn (Source Candidate)
            $lockedSource->update([
                'status' => 'merged',
                'merged_into_candidate_id' => $lockedTarget->id,
                'merged_at' => now(),
                'merged_by' => $actor->id,
                'merge_reason' => $reason,
            ]);

            return $lockedSource;
        });
    }
}
