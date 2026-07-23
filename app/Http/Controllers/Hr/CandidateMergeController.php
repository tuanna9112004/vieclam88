<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Candidate\MergeCandidateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Candidate\CandidateMergeRequest;
use App\Models\Candidate;
use Illuminate\Http\RedirectResponse;

class CandidateMergeController extends Controller
{
    public function store(CandidateMergeRequest $request, Candidate $candidate, MergeCandidateAction $action): RedirectResponse
    {
        $target = Candidate::findOrFail($request->validated('target_candidate_id'));

        $action->handle(
            $candidate,
            $target,
            $request->user(),
            $request->validated('reason'),
            $request->validated('kept_application_id'),
        );

        return redirect()
            ->route('hr.candidates.show', $target)
            ->with('status', 'Đã gộp ứng viên thành công.');
    }
}
