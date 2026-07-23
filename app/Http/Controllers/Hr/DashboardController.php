<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Dashboard\GetAdminDashboardStatsAction;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, GetAdminDashboardStatsAction $adminStatsAction): View
    {
        $user = $request->user();

        $filters = $request->only([
            'owner_branch_id',
            'company_id',
            'job_id',
            'date_from',
            'date_to',
        ]);

        $stats = $adminStatsAction->handle($user, $filters);

        $branches = $user->isSuperAdmin() ? Branch::orderBy('name')->get(['id', 'name']) : collect();
        $companies = $user->isSuperAdmin() ? Company::orderBy('name')->get(['id', 'name']) : collect();
        $jobs = $user->isSuperAdmin() ? Job::orderBy('title')->get(['id', 'title']) : collect();

        return view('hr.dashboard', compact('stats', 'branches', 'companies', 'jobs', 'filters'));
    }
}
