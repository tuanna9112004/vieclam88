<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Job\CloseJobAction;
use App\Actions\Job\PauseJobAction;
use App\Actions\Job\PublishJobAction;
use App\Enums\JobCloseReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Job\CloseJobRequest;
use App\Http\Requests\Hr\Job\PauseJobRequest;
use App\Http\Requests\Hr\Job\PublishJobRequest;
use App\Models\Job;
use Illuminate\Http\RedirectResponse;

class JobWorkflowController extends Controller
{
    public function publish(PublishJobRequest $request, Job $job, PublishJobAction $action): RedirectResponse
    {
        $action->handle($job, $request->user(), $request->validated('verification_override_reason'));

        return redirect()->route('hr.jobs.index')->with('status', 'Đã xuất bản Job.');
    }

    public function pause(PauseJobRequest $request, Job $job, PauseJobAction $action): RedirectResponse
    {
        $action->handle($job, $request->user(), $request->validated('reason'));

        return redirect()->route('hr.jobs.index')->with('status', 'Đã tạm dừng Job.');
    }

    public function close(CloseJobRequest $request, Job $job, CloseJobAction $action): RedirectResponse
    {
        $action->handle($job, $request->user(), JobCloseReason::from($request->validated('close_reason')));

        return redirect()->route('hr.jobs.index')->with('status', 'Đã đóng Job.');
    }
}
