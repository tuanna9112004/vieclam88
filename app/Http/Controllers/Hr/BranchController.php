<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Models\AdministrativeUnit;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Branch::class);

        $branches = Branch::query()->orderBy('name')->paginate(20);
        $trashedBranches = Branch::onlyTrashed()->orderBy('name')->get();

        return view('hr.branches.index', compact('branches', 'trashedBranches'));
    }

    public function create(): View
    {
        $this->authorize('create', Branch::class);

        $administrativeUnits = AdministrativeUnit::where('is_active', true)->orderBy('name')->get();

        return view('hr.branches.create', compact('administrativeUnits'));
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        Branch::create($request->validated());

        return redirect()->route('hr.branches.index')->with('status', 'Đã tạo cơ sở.');
    }

    public function edit(Branch $branch): View
    {
        $this->authorize('update', $branch);

        $administrativeUnits = AdministrativeUnit::where('is_active', true)->orderBy('name')->get();

        return view('hr.branches.edit', compact('branch', 'administrativeUnits'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $data = $request->validated();

        $this->guardAgainstLeavingStaffOrphaned($branch, $data['status'] === 'inactive');

        $branch->update($data);

        return redirect()->route('hr.branches.index')->with('status', 'Đã cập nhật cơ sở.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        $this->guardAgainstLeavingStaffOrphaned($branch, true);

        $branch->delete();

        return redirect()->route('hr.branches.index')->with('status', 'Đã xóa cơ sở.');
    }

    public function restore(Branch $branch): RedirectResponse
    {
        $this->authorize('restore', $branch);

        $branch->restore();

        return redirect()->route('hr.branches.index')->with('status', 'Đã khôi phục cơ sở.');
    }

    /**
     * Ngừng hoạt động/xóa cơ sở trong khi vẫn còn nhân viên gán vào đó để họ bị "mồ côi" —
     * bắt buộc Admin chuyển nhân viên sang cơ sở khác trước (không có route transfer riêng ở
     * Phase 1, dùng chung hr.staff.update).
     */
    protected function guardAgainstLeavingStaffOrphaned(Branch $branch, bool $isRemovingBranch): void
    {
        if ($isRemovingBranch && $branch->users()->exists()) {
            throw ValidationException::withMessages([
                'branch' => 'Cơ sở còn nhân viên — chuyển nhân viên sang cơ sở khác trước khi ngừng hoạt động hoặc xóa.',
            ]);
        }
    }
}
