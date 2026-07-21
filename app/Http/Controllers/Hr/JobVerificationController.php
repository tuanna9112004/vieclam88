<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Job\SaveJobVerificationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Job\StoreJobVerificationRequest;
use App\Models\Job;
use Illuminate\Http\RedirectResponse;

class JobVerificationController extends Controller
{
    public function store(StoreJobVerificationRequest $request, Job $job, SaveJobVerificationAction $action): RedirectResponse
    {
        $action->handle($job, $request->validated(), $request->user());

        return redirect()->route('hr.jobs.index')->with('status', 'Đã ghi nhận xác nhận.');
    }
}
