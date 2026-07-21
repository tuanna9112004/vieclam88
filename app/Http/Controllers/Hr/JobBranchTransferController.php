<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Job\ChangeJobBranchAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Job\TransferJobBranchRequest;
use App\Models\Branch;
use App\Models\Job;
use Illuminate\Http\RedirectResponse;

class JobBranchTransferController extends Controller
{
    public function store(TransferJobBranchRequest $request, Job $job, ChangeJobBranchAction $action): RedirectResponse
    {
        $toBranch = Branch::findOrFail($request->validated('to_branch_id'));

        $action->handle($job, $toBranch, $request->user(), $request->validated('reason'));

        return redirect()->route('hr.jobs.index')->with('status', 'Đã chuyển cơ sở phụ trách.');
    }
}
