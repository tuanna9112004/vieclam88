<?php

namespace App\Http\Controllers\Hr;

use App\Actions\CompanyLocation\SaveCompanyLocationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\CompanyLocation\StoreCompanyLocationRequest;
use App\Http\Requests\Hr\CompanyLocation\UpdateCompanyLocationRequest;
use App\Models\AdministrativeUnit;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\IndustrialPark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyLocationController extends Controller
{
    public function index(Request $request, Company $company): View|JsonResponse
    {
        $this->authorize('viewAny', CompanyLocation::class);

        $locations = $company->companyLocations()->orderBy('name')->get();

        // Quick Create tu form Job: dropdown Location loc theo Company qua AJAX goi lai dung
        // route nay, chi khac dinh dang response.
        if ($request->wantsJson()) {
            return response()->json($locations->map(fn ($location) => [
                'id' => $location->id,
                'name' => $location->name,
            ]));
        }

        $administrativeUnits = AdministrativeUnit::where('is_active', true)->orderBy('name')->get();
        $industrialParks = IndustrialPark::where('is_active', true)->orderBy('name')->get();

        return view('hr.companies.locations.index', compact(
            'company', 'locations', 'administrativeUnits', 'industrialParks'
        ));
    }

    public function store(StoreCompanyLocationRequest $request, Company $company, SaveCompanyLocationAction $action): RedirectResponse|JsonResponse
    {
        $location = $action->handle($request->validated(), $company);

        if ($request->wantsJson()) {
            return response()->json(['id' => $location->id, 'name' => $location->name]);
        }

        return redirect()->route('hr.company-locations.index', $company)->with('status', 'Đã tạo địa điểm.');
    }

    public function update(
        UpdateCompanyLocationRequest $request,
        Company $company,
        CompanyLocation $location,
        SaveCompanyLocationAction $action
    ): RedirectResponse {
        abort_unless($location->company_id === $company->id, 404);

        $action->handle($request->validated(), $company, $location);

        return redirect()->route('hr.company-locations.index', $company)->with('status', 'Đã cập nhật địa điểm.');
    }

    public function destroy(Company $company, CompanyLocation $location): RedirectResponse
    {
        abort_unless($location->company_id === $company->id, 404);

        $this->authorize('delete', $location);

        // ADR-045 mục "không xóa khi Job đang dùng": job_locations đã tồn tại — chặn xóa nếu còn
        // Job nào tham chiếu location này (kể cả Job đã đóng, vì lịch sử vẫn cần giữ nguyên).
        if ($location->jobLocations()->exists()) {
            abort(422, 'Địa điểm đang được Job sử dụng — không thể xóa.');
        }

        $location->delete();

        return redirect()->route('hr.company-locations.index', $company)->with('status', 'Đã xóa địa điểm.');
    }

    public function restore(Company $company, CompanyLocation $location): RedirectResponse
    {
        abort_unless($location->company_id === $company->id, 404);

        $this->authorize('restore', $location);

        $location->restore();

        return redirect()->route('hr.company-locations.index', $company)->with('status', 'Đã khôi phục địa điểm.');
    }
}
