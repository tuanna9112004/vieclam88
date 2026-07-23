<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Candidate\AnonymizeCandidateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Candidate\CandidateAnonymizeRequest;
use App\Models\Candidate;
use Illuminate\Http\RedirectResponse;

class CandidateAnonymizeController extends Controller
{
    public function store(CandidateAnonymizeRequest $request, Candidate $candidate, AnonymizeCandidateAction $action): RedirectResponse
    {
        $action->handle($candidate, $request->user());

        return redirect()
            ->route('hr.candidates.show', $candidate)
            ->with('status', 'Đã ẩn danh ứng viên. Hành động này không thể hoàn tác.');
    }
}
