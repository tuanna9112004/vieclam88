<?php

namespace App\Actions\Candidate;

use App\Enums\CandidateDuplicateReviewStatus;
use App\Models\Application;
use App\Models\CandidateDuplicateReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * docs/CORE-FLOWS.md mục 6.2.2, ADR-062 — xử lý nghi ngờ trùng lặp candidate_duplicate_reviews.
 * Không tự merge candidate. Khi giải quyết xong dòng review cuối cùng của Application,
 * tự động cập nhật applications.needs_duplicate_review = false.
 */
class ResolveCandidateDuplicateReviewAction
{
    public function handle(
        CandidateDuplicateReview $review,
        CandidateDuplicateReviewStatus $status,
        ?string $note,
        User $actor
    ): CandidateDuplicateReview {
        if ($status === CandidateDuplicateReviewStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Không thể đổi trạng thái review về pending.',
            ]);
        }

        return DB::transaction(function () use ($review, $status, $note, $actor) {
            /** @var CandidateDuplicateReview $lockedReview */
            $lockedReview = CandidateDuplicateReview::whereKey($review->id)->lockForUpdate()->firstOrFail();

            $lockedReview->update([
                'status' => $status,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            $pendingCount = CandidateDuplicateReview::where('application_id', $lockedReview->application_id)
                ->where('status', CandidateDuplicateReviewStatus::Pending->value)
                ->count();

            if ($pendingCount === 0) {
                Application::where('id', $lockedReview->application_id)->update([
                    'needs_duplicate_review' => false,
                    'duplicate_reviewed_at' => now(),
                    'duplicate_reviewed_by' => $actor->id,
                ]);
            } else {
                Application::where('id', $lockedReview->application_id)->update([
                    'needs_duplicate_review' => true,
                    'duplicate_reviewed_at' => null,
                    'duplicate_reviewed_by' => null,
                ]);
            }

            return $lockedReview;
        });
    }
}
