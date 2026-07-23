<?php

namespace App\Http\Controllers\Public;

use App\Actions\Application\CreateApplicationAction;
use App\Exceptions\SubmissionLockTimeoutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreApplicationRequest;
use App\Models\Job;
use Illuminate\Http\RedirectResponse;

class ApplicationController extends Controller
{
    public function store(StoreApplicationRequest $request, Job $job, CreateApplicationAction $action): RedirectResponse
    {
        // "Job con active" phai duoc kiem tra lai o server du UI da an CTA (docs/CORE-FLOWS.md muc 3).
        if (! $job->isOpenForApplication()) {
            return redirect()->route('jobs.show', $job->slug)
                ->with('error', 'Tin tuyển dụng này hiện không còn nhận hồ sơ.');
        }

        $data = $request->validated();

        try {
            $action->handle($job, $data, $data['submission_token'], [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (SubmissionLockTimeoutException $e) {
            // ADR-061 buoc 3 (docs/CORE-FLOWS.md muc 3.1): timeout GET_LOCK phai tra loi than
            // thien, khong duoc de bubble thanh 500.
            return redirect()->route('jobs.show', $job->slug)->with('error', $e->getMessage());
        }

        return redirect()->route('jobs.show', $job->slug)
            ->with('success', 'Cảm ơn bạn đã ứng tuyển! Chúng tôi sẽ liên hệ với bạn sớm.');
    }
}
