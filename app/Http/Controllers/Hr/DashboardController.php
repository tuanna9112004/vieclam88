<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Dashboard\GetDashboardStatsAction;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, GetDashboardStatsAction $statsAction): View
    {
        $user = $request->user();
        $selectedBranchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;

        $stats = $statsAction->handle($user, $selectedBranchId);
        $branches = $user->isAdmin() ? Branch::orderBy('name')->get(['id', 'name']) : collect();

        return view('hr.dashboard', compact('stats', 'branches', 'selectedBranchId'));
    }
}
