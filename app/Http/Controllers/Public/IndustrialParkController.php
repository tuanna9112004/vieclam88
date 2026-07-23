<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\IndustrialPark;
use App\Models\Job;
use Illuminate\View\View;

class IndustrialParkController extends Controller
{
    public function show(IndustrialPark $industrialPark): View
    {
        $industrialPark->load('administrativeUnit:id,name,is_active');

        abort_unless(
            $industrialPark->is_active && $industrialPark->administrativeUnit?->is_active,
            404
        );

        $jobs = Job::query()
            ->publiclyListed()
            ->whereHas('company', fn ($query) => $query->where('status', 'active'))
            ->whereHas('ownerBranch', fn ($query) => $query->where('status', 'active'))
            ->inIndustrialPark($industrialPark->id)
            ->with([
                'company:id,name',
                'ownerBranch:id,name,phone,zalo',
                'jobLocations' => fn ($query) => $query->where('is_primary', true),
                'jobLocations.companyLocation:id,name,administrative_unit_id',
                'jobLocations.companyLocation.administrativeUnit:id,name',
                'jobWorkShifts.workShift:id,name',
            ])
            ->latest('published_at')
            ->paginate(12);

        return view('public.industrial-parks.show', [
            'industrialPark' => $industrialPark,
            'jobs' => $jobs,
        ]);
    }
}
