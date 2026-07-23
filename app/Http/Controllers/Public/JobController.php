<?php

namespace App\Http\Controllers\Public;

use App\Actions\Application\IssueSubmissionTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\JobIndexRequest;
use App\Models\AdministrativeUnit;
use App\Models\IndustrialPark;
use App\Models\Job;
use App\Models\WorkShift;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobController extends Controller
{
    public function index(JobIndexRequest $request): View
    {
        $filters = $request->validated();

        $jobs = Job::query()
            ->publiclyListed()
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->searchKeyword($v))
            ->when($filters['company_id'] ?? null, fn ($q, $v) => $q->inCompany((int) $v))
            ->when($filters['industrial_park_id'] ?? null, fn ($q, $v) => $q->inIndustrialPark($v))
            ->when($filters['administrative_unit_id'] ?? null, fn ($q, $v) => $q->inAdministrativeUnit($v))
            ->when($filters['work_shift_id'] ?? null, fn ($q, $v) => $q->withWorkShift($v))
            ->when($filters['salary'] ?? null, fn ($q, $v) => $q->salaryBucket($v))
            ->when($filters['shuttle_bus'] ?? null, fn ($q) => $q->hasShuttleBus())
            ->when($filters['accommodation'] ?? null, fn ($q) => $q->hasAccommodation())
            ->sortListing($filters['sort'] ?? null)
            ->with([
                'company:id,name',
                'ownerBranch:id,name,phone,zalo',
                'jobLocations' => fn ($q) => $q->where('is_primary', true),
                'jobLocations.companyLocation:id,name,administrative_unit_id,industrial_park_id',
                'jobLocations.companyLocation.administrativeUnit:id,name',
                'jobLocations.companyLocation.industrialPark:id,name',
                'jobWorkShifts.workShift:id,name',
            ])
            ->paginate(12)
            ->withQueryString();

        $industrialParks = IndustrialPark::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $administrativeUnits = AdministrativeUnit::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $workShifts = WorkShift::where('is_active', true)->orderBy('sort_order')->get(['id', 'name']);

        return view('public.jobs.index', [
            'jobs' => $jobs,
            'filters' => $filters,
            'industrialParks' => $industrialParks,
            'administrativeUnits' => $administrativeUnits,
            'workShifts' => $workShifts,
        ]);
    }

    public function show(Request $request, Job $job, IssueSubmissionTokenAction $issueSubmissionToken): View
    {
        // Job draft "chưa từng publish" không có trang chi tiết công khai (docs/CORE-FLOWS.md mục 2).
        abort_if($job->status === 'draft', 404);

        // Submission Token Lifecycle (docs/CORE-FLOWS.md mục 3, ADR-041): chỉ sinh token khi Job
        // còn nhận Application — form ứng tuyển thật thuộc slice sau, token đã sẵn sàng trong
        // session cho slice đó dùng.
        $isOpen = $job->isOpenForApplication();
        $submissionToken = $isOpen ? $issueSubmissionToken->handle($request->session(), $job->id) : null;

        // "Nơi ở hiện tại" trong form ứng tuyển — 1 dropdown đơn dùng chung administrative_units
        // đang active (chỉ cần khi Job còn mở, giống filter administrativeUnits ở jobs.index).
        $applicantAdministrativeUnits = $isOpen
            ? AdministrativeUnit::where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : collect();

        $job->load([
            'company:id,name,industry',
            'ownerBranch:id,name,phone,zalo',
            'companyContact',
            'jobLocations.companyLocation:id,name,address_detail,administrative_unit_id,industrial_park_id',
            'jobLocations.companyLocation.administrativeUnit:id,name',
            'jobLocations.companyLocation.industrialPark:id,name',
            'jobWorkShifts.workShift:id,name',
        ]);

        $relatedJobs = Job::query()
            ->publiclyListed()
            ->where('id', '!=', $job->id)
            ->where(function ($q) use ($job) {
                $q->where('company_id', $job->company_id);

                if ($job->company?->industry) {
                    $q->orWhereHas('company', fn ($c) => $c->where('industry', $job->company->industry));
                }
            })
            ->with(['company:id,name'])
            ->latest('published_at')
            ->limit(4)
            ->get();

        return view('public.jobs.show', [
            'job' => $job,
            'relatedJobs' => $relatedJobs,
            'submissionToken' => $submissionToken,
            'applicantAdministrativeUnits' => $applicantAdministrativeUnits,
        ]);
    }
}
