<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::query()
            ->where('status', 'active')
            ->withCount([
                'jobs as public_jobs_count' => fn ($query) => $query
                    ->publiclyListed()
                    ->whereHas('ownerBranch', fn ($branchQuery) => $branchQuery->where('status', 'active')),
            ])
            ->orderBy('name')
            ->paginate(12);

        return view('public.companies.index', ['companies' => $companies]);
    }

    public function show(Company $company): View
    {
        abort_unless($company->status === 'active', 404);

        $jobs = $company->jobs()
            ->publiclyListed()
            ->whereHas('ownerBranch', fn ($query) => $query->where('status', 'active'))
            ->with($this->jobCardRelations())
            ->latest('published_at')
            ->paginate(12);

        return view('public.companies.show', [
            'company' => $company,
            'jobs' => $jobs,
        ]);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function jobCardRelations(): array
    {
        return [
            'company:id,name',
            'ownerBranch:id,name,phone,zalo',
            'jobLocations' => fn ($query) => $query->where('is_primary', true),
            'jobLocations.companyLocation:id,name,administrative_unit_id',
            'jobLocations.companyLocation.administrativeUnit:id,name',
            'jobWorkShifts.workShift:id,name',
        ];
    }
}
