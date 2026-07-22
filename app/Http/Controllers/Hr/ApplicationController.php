<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Application\BuildApplicationTimelineAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Application\IndexApplicationRequest;
use App\Models\Application;
use App\Models\Branch;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(IndexApplicationRequest $request): View
    {
        $this->authorize('viewAny', Application::class);

        $user = $request->user();
        $filters = $request->validated();

        $applications = Application::query()
            ->with(['candidate:id,full_name', 'job:id,title,company_id', 'job.company:id,name', 'ownerBranch:id,name'])
            // Staff luon bi khoa theo co so minh, khong doc owner_branch_id tu request du co
            // gui kem (docs/CORE-FLOWS.md muc 9.2, ACCEPTANCE-CRITERIA.md muc 4).
            ->when(
                ! $user->isAdmin(),
                fn ($q) => $q->where('owner_branch_id', $user->branch_id),
                fn ($q) => $q->when(
                    ! empty($filters['owner_branch_id']),
                    fn ($q2) => $q2->whereIn('owner_branch_id', $filters['owner_branch_id'])
                )
            )
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->searchCandidate($v))
            ->when($filters['job_id'] ?? null, fn ($q, $v) => $q->where('job_id', $v))
            ->when($filters['company_id'] ?? null, fn ($q, $v) => $q->whereHas('job', fn ($jq) => $jq->where('company_id', $v)))
            ->when($filters['stage'] ?? null, fn ($q, $v) => $q->where('stage', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filters['uncontacted'] ?? null, fn ($q) => $q->uncontacted())
            ->when($filters['processing'] ?? null, fn ($q) => $q->whereIn('stage', ['contacting', 'consulted', 'interview_scheduled', 'interviewed', 'waiting_start']))
            ->when($filters['has_callback'] ?? null, fn ($q) => $q->hasScheduledAppointment('callback'))
            ->when($filters['callback_today'] ?? null, fn ($q) => $q->hasScheduledAppointment('callback', today()->toDateString()))
            ->when($filters['has_interview'] ?? null, fn ($q) => $q->hasScheduledAppointment('interview'))
            ->when($filters['interview_today'] ?? null, fn ($q) => $q->hasScheduledAppointment('interview', today()->toDateString()))
            ->when($filters['needs_duplicate_review'] ?? null, fn ($q) => $q->where('needs_duplicate_review', true))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $branches = $user->isAdmin() ? Branch::orderBy('name')->get(['id', 'name']) : collect();

        return view('hr.applications.index', compact('applications', 'filters', 'branches'));
    }

    public function show(Application $application, BuildApplicationTimelineAction $timelineAction): View
    {
        $this->authorize('view', $application);

        $application->load(['candidate', 'job.company', 'ownerBranch']);
        $timeline = $timelineAction->handle($application);

        return view('hr.applications.show', compact('application', 'timeline'));
    }
}
