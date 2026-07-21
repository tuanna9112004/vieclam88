<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeUnit;
use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $featuredJobs = Job::query()
            ->publiclyListed()
            ->where('is_urgent', true)
            ->with($this->cardRelations())
            ->latest('published_at')
            ->limit(6)
            ->get();

        $newestJobs = Job::query()
            ->publiclyListed()
            ->with($this->cardRelations())
            ->latest('published_at')
            ->limit(8)
            ->get();

        $regions = $this->topRegions();

        $administrativeUnits = AdministrativeUnit::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('public.home', [
            'featuredJobs' => $featuredJobs,
            'newestJobs' => $newestJobs,
            'regions' => $regions,
            'administrativeUnits' => $administrativeUnits,
        ]);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function cardRelations(): array
    {
        return [
            'company:id,name',
            'jobLocations' => fn ($q) => $q->where('is_primary', true),
            'jobLocations.companyLocation:id,name,administrative_unit_id',
            'jobLocations.companyLocation.administrativeUnit:id,name',
        ];
    }

    /**
     * Top đơn vị hành chính theo số Job đang tuyển (published, chưa hết hạn, chưa xóa, qua địa
     * điểm chính đang active) — 1 query gộp (group by) + 1 query tên đơn vị, không N+1.
     *
     * @return \Illuminate\Support\Collection<int, AdministrativeUnit>
     */
    private function topRegions(): \Illuminate\Support\Collection
    {
        $counts = DB::table('jobs')
            ->join('job_locations', function ($join) {
                $join->on('job_locations.job_id', '=', 'jobs.id')->where('job_locations.is_primary', true);
            })
            ->join('company_locations', 'company_locations.id', '=', 'job_locations.company_location_id')
            ->where('jobs.status', 'published')
            ->where(fn ($q) => $q->whereNull('jobs.expires_at')->orWhere('jobs.expires_at', '>=', now()))
            ->whereNull('jobs.deleted_at')
            ->whereNull('company_locations.deleted_at')
            ->where('company_locations.status', 'active')
            ->whereNotNull('company_locations.administrative_unit_id')
            ->select('company_locations.administrative_unit_id', DB::raw('COUNT(*) as jobs_count'))
            ->groupBy('company_locations.administrative_unit_id')
            ->orderByDesc('jobs_count')
            ->limit(6)
            ->pluck('jobs_count', 'administrative_unit_id');

        if ($counts->isEmpty()) {
            return collect();
        }

        return AdministrativeUnit::whereIn('id', $counts->keys())
            ->get(['id', 'name'])
            ->map(function (AdministrativeUnit $unit) use ($counts) {
                $unit->jobs_count = $counts[$unit->id];

                return $unit;
            })
            ->sortByDesc('jobs_count')
            ->values();
    }
}
