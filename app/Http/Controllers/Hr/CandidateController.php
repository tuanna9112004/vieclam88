<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\CandidateDuplicateReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CandidateController extends Controller
{
    public function show(Candidate $candidate): View|RedirectResponse
    {
        $this->authorize('view', $candidate);

        // Candidate nguon (da merge) luon hien thi theo root — khong 404, tranh gay link cu
        // (docs/CORE-FLOWS.md muc 6.3).
        $root = $candidate->resolveRoot();
        if ((int) $root->id !== (int) $candidate->id) {
            return redirect()->route('hr.candidates.show', $root);
        }

        $root->load(['currentWard.province', 'currentAdministrativeUnit']);

        $user = auth()->user();
        $familyIds = $root->getMergedFamilyIds();

        // Staff chi thay Application dung co so minh trong toan bo merged family; Admin khong
        // gioi han (docs/CORE-FLOWS.md muc 6.3 buoc 3, muc 6.4).
        $applications = Application::whereIn('candidate_id', $familyIds)
            ->with(['job.company', 'ownerBranch', 'candidate'])
            ->when(! $user->isAdmin(), fn ($q) => $q->where('owner_branch_id', $user->branch_id))
            ->latest()
            ->get();

        $mergedSources = Candidate::whereIn('id', $familyIds)
            ->where('id', '!=', $root->id)
            ->with('mergedBy')
            ->get();

        // Duplicate review chi Admin duoc xem (docs/CORE-FLOWS.md muc 6.2.2: "Chi Admin truy
        // cap; Staff bi 403" ap dung cho toan bo du lieu review, ke ca hien thi tom tat o day).
        $duplicateReviews = $user->isAdmin()
            ? CandidateDuplicateReview::query()
                ->where(function ($q) use ($familyIds) {
                    $q->whereIn('candidate_id', $familyIds)->orWhereIn('suspected_candidate_id', $familyIds);
                })
                ->where('status', 'pending')
                ->with(['candidate', 'suspectedCandidate', 'application'])
                ->get()
            : collect();

        return view('hr.candidates.show', [
            'candidate' => $root,
            'applications' => $applications,
            'mergedSources' => $mergedSources,
            'duplicateReviews' => $duplicateReviews,
        ]);
    }
}
