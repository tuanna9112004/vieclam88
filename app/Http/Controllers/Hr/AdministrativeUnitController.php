<?php

namespace App\Http\Controllers\Hr;

use App\Actions\AdministrativeUnit\UpsertAdministrativeUnitAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\AdministrativeUnit\IndexAdministrativeUnitRequest;
use App\Http\Requests\Hr\AdministrativeUnit\StoreAdministrativeUnitRequest;
use App\Http\Requests\Hr\AdministrativeUnit\UpdateAdministrativeUnitRequest;
use App\Models\AdministrativeUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdministrativeUnitController extends Controller
{
    public function index(IndexAdministrativeUnitRequest $request): View
    {
        $filters = $request->validated();

        $administrativeUnits = AdministrativeUnit::query()
            ->with('parent')
            ->when($filters['q'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('official_code', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('parent_id IS NOT NULL')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $editingUnit = isset($filters['edit'])
            ? AdministrativeUnit::query()->findOrFail($filters['edit'])
            : null;

        $createParentOptions = AdministrativeUnit::query()
            ->orderBy('name')
            ->get();

        $editParentOptions = collect();

        if ($editingUnit) {
            $excludedIds = [$editingUnit->id, ...$editingUnit->descendantIds()];
            $editParentOptions = AdministrativeUnit::query()
                ->whereNotIn('id', $excludedIds)
                ->orderBy('name')
                ->get();
        }

        $typeLabels = [
            'province' => 'Tỉnh',
            'city' => 'Thành phố',
            'commune' => 'Xã',
            'ward' => 'Phường',
            'special_zone' => 'Đặc khu',
            'legacy_district' => 'Cấp huyện cũ',
        ];

        return view('hr.administrative-units.index', compact(
            'administrativeUnits',
            'createParentOptions',
            'editParentOptions',
            'editingUnit',
            'typeLabels'
        ));
    }

    public function store(
        StoreAdministrativeUnitRequest $request,
        UpsertAdministrativeUnitAction $action
    ): RedirectResponse {
        $action->handle($request->validated());

        return redirect()
            ->route('hr.administrative-units.index')
            ->with('status', 'Đã lưu đơn vị hành chính.');
    }

    public function update(
        UpdateAdministrativeUnitRequest $request,
        AdministrativeUnit $administrativeUnit,
        UpsertAdministrativeUnitAction $action
    ): RedirectResponse {
        $action->handle($request->validated(), $administrativeUnit);

        return redirect()
            ->route('hr.administrative-units.index')
            ->with('status', 'Đã cập nhật đơn vị hành chính.');
    }
}
