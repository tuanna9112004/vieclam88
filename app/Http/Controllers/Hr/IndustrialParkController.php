<?php

namespace App\Http\Controllers\Hr;

use App\Actions\IndustrialPark\SaveIndustrialParkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\IndustrialPark\StoreIndustrialParkRequest;
use App\Http\Requests\Hr\IndustrialPark\UpdateIndustrialParkRequest;
use App\Models\AdministrativeUnit;
use App\Models\IndustrialPark;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IndustrialParkController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', IndustrialPark::class);

        $industrialParks = IndustrialPark::query()
            ->with('administrativeUnit')
            ->orderBy('name')
            ->paginate(20);

        $administrativeUnits = AdministrativeUnit::where('is_active', true)->orderBy('name')->get();

        return view('hr.industrial-parks.index', compact('industrialParks', 'administrativeUnits'));
    }

    public function store(StoreIndustrialParkRequest $request, SaveIndustrialParkAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->route('hr.industrial-parks.index')->with('status', 'Đã tạo khu công nghiệp.');
    }

    public function update(
        UpdateIndustrialParkRequest $request,
        IndustrialPark $industrialPark,
        SaveIndustrialParkAction $action
    ): RedirectResponse {
        $action->handle($request->validated(), $industrialPark);

        return redirect()->route('hr.industrial-parks.index')->with('status', 'Đã cập nhật khu công nghiệp.');
    }
}
