<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Application\TransferApplicationBranchAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Application\TransferApplicationBranchRequest;
use App\Models\Application;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;

class ApplicationBranchTransferController extends Controller
{
    public function store(
        TransferApplicationBranchRequest $request,
        Application $application,
        TransferApplicationBranchAction $action
    ): RedirectResponse {
        $toBranch = Branch::findOrFail($request->validated('to_branch_id'));

        $action->handle($application, $toBranch, $request->user(), $request->validated('reason'));

        return redirect()
            ->route('hr.applications.show', $application)
            ->with('status', 'Đã chuyển cơ sở phụ trách hồ sơ.');
    }
}
