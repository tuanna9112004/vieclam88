<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Job\SaveJobDraftAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Job\StoreJobRequest;
use App\Http\Requests\Hr\Job\UpdateJobRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Job;
use App\Support\JobVerificationWarning;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JobController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Job::class);

        $jobs = Job::query()->with(['company', 'ownerBranch'])->latest()->paginate(20);

        // Tinh muc canh bao xac minh tung dong tu 1 lan doc settings duy nhat (khong N+1) —
        // cung nguon logic voi Dashboard (App\Support\JobVerificationWarning).
        $verificationThresholds = JobVerificationWarning::thresholds();
        $verificationLevels = $jobs->getCollection()->mapWithKeys(
            fn (Job $job) => [$job->id => JobVerificationWarning::level($job, $verificationThresholds)]
        );

        return view('hr.jobs.index', compact('jobs', 'verificationLevels'));
    }

    public function create(): View
    {
        $this->authorize('create', Job::class);

        $companies = Company::orderBy('name')->get();
        $branches = auth()->user()->isAdmin()
            ? Branch::where('status', 'active')->orderBy('name')->get()
            : collect();

        return view('hr.jobs.create', compact('companies', 'branches'));
    }

    public function store(StoreJobRequest $request, SaveJobDraftAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $request->user());

        return redirect()->route('hr.jobs.index')->with('status', 'Đã tạo Job (nháp).');
    }

    public function edit(Job $job): View
    {
        $this->authorize('update', $job);

        $companies = Company::orderBy('name')->get();
        $primaryLocation = $job->jobLocations()->where('is_primary', true)->first()?->companyLocation;

        return view('hr.jobs.edit', compact('job', 'companies', 'primaryLocation'));
    }

    public function update(UpdateJobRequest $request, Job $job, SaveJobDraftAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $request->user(), $job);

        return redirect()->route('hr.jobs.index')->with('status', 'Đã cập nhật Job.');
    }
}
