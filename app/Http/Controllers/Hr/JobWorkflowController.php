<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Job\PublishJobAction;
use App\Http\Controllers\Controller;
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
}
