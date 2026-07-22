<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Candidate\ResolveCandidateDuplicateReviewAction;
use App\Enums\CandidateDuplicateReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Candidate\ResolveCandidateDuplicateReviewRequest;
use App\Models\CandidateDuplicateReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DuplicateReviewController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', CandidateDuplicateReview::class);

        $statusFilter = $request->input('status', 'pending');

        $query = CandidateDuplicateReview::query()
            ->with([
                'application.job',
                'candidate',
                'suspectedCandidate',
                'reviewedBy',
            ]);

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $reviews = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('hr.duplicate-reviews.index', [
            'reviews' => $reviews,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function show(CandidateDuplicateReview $duplicateReview): View
    {
        $this->authorize('view', $duplicateReview);

        $duplicateReview->loadMissing([
            'application.job.company',
            'candidate',
            'suspectedCandidate',
            'reviewedBy',
        ]);

        $otherReviews = CandidateDuplicateReview::query()
            ->where('application_id', $duplicateReview->application_id)
            ->where('id', '!=', $duplicateReview->id)
            ->with(['candidate', 'suspectedCandidate', 'reviewedBy'])
            ->get();

        return view('hr.duplicate-reviews.show', [
            'review' => $duplicateReview,
            'otherReviews' => $otherReviews,
        ]);
    }

    public function resolve(
        ResolveCandidateDuplicateReviewRequest $request,
        CandidateDuplicateReview $duplicateReview,
        ResolveCandidateDuplicateReviewAction $action
    ): RedirectResponse {
        $status = CandidateDuplicateReviewStatus::from($request->validated('status'));
        $note = $request->validated('review_note');

        $action->handle($duplicateReview, $status, $note, $request->user());

        return redirect()
            ->route('hr.duplicate-reviews.index')
            ->with('status', 'Đã xử lý nghi ngờ trùng lặp thành công.');
    }
}
