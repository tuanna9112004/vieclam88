<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\Province;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Branch::class);
        $actor = auth()->user();

        $branches = Branch::query()
            ->when($actor->isBranchAdmin(), fn ($query) => $query->whereKey($actor->branch_id))
            ->with(['ward.province', 'administrativeUnit'])
            ->orderBy('name')
            ->paginate(20);
        $trashedBranches = $actor->isSuperAdmin()
            ? Branch::onlyTrashed()->orderBy('name')->get()
            : collect();

        return view('hr.branches.index', compact('branches', 'trashedBranches'));
    }

    public function create(): View
    {
        $this->authorize('create', Branch::class);

        [$provinces, $wards] = $this->wardSelectOptions();

        return view('hr.branches.create', compact('provinces', 'wards'));
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        Branch::create($request->validated());

        return redirect()->route('hr.branches.index')->with('status', 'Đã tạo cơ sở.');
    }

    public function edit(Branch $branch): View
    {
        $this->authorize('update', $branch);

        [$provinces, $wards] = $this->wardSelectOptions();

        return view('hr.branches.edit', compact('branch', 'provinces', 'wards'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($branch, $data): void {
            $lockedBranch = Branch::query()
                ->whereKey($branch->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardAgainstLeavingStaffOrphaned($lockedBranch, $data['status'] === 'inactive');
            $lockedBranch->update($data);
        });

        return redirect()->route('hr.branches.index')->with('status', 'Đã cập nhật cơ sở.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        DB::transaction(function () use ($branch): void {
            $lockedBranch = Branch::query()
                ->whereKey($branch->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardAgainstLeavingStaffOrphaned($lockedBranch, true);
            $lockedBranch->delete();
        });

        return redirect()->route('hr.branches.index')->with('status', 'Đã xóa cơ sở.');
    }

    public function restore(Branch $branch): RedirectResponse
    {
        $this->authorize('restore', $branch);

        $branch->restore();

        return redirect()->route('hr.branches.index')->with('status', 'Đã khôi phục cơ sở.');
    }

    /**
     * TASK 1.3: nguồn cho component province-ward-select ở form create/edit.
     *
     * @return array{0: Collection, 1: Collection}
     */
    protected function wardSelectOptions(): array
    {
        return [
            Province::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            Ward::where('is_active', true)->orderBy('name')->get(['id', 'name', 'province_id']),
        ];
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
