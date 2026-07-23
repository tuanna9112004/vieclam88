<?php

namespace App\Http\Controllers\Hr;

use App\Actions\User\CreateStaffAction;
use App\Actions\User\ResetStaffPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Staff\ResetStaffPasswordRequest;
use App\Http\Requests\Hr\Staff\StoreStaffRequest;
use App\Http\Requests\Hr\Staff\UpdateStaffRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);
        $actor = auth()->user();
        $managedRoles = $actor->isSuperAdmin()
            ? ['staff', 'branch_admin']
            : ['staff'];

        $staff = User::query()
            ->whereIn('role', $managedRoles)
            ->when($actor->isBranchAdmin(), fn ($query) => $query->where('branch_id', $actor->branch_id))
            ->with('branch')
            ->orderBy('name')
            ->paginate(20);

        return view('hr.staff.index', compact('staff'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $branches = $this->manageableBranches();

        return view('hr.staff.create', compact('branches'));
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        (new CreateStaffAction)->handle($request->validated(), $request->user());

        return redirect()->route('hr.staff.index')->with('status', 'Đã tạo nhân viên.');
    }

    public function edit(User $staff): View
    {
        $this->authorize('update', $staff);

        $branches = $this->manageableBranches();

        return view('hr.staff.edit', compact('staff', 'branches'));
    }

    public function update(UpdateStaffRequest $request, User $staff): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $staff): void {
            $branch = Branch::query()
                ->whereKey($data['branch_id'])
                ->lockForUpdate()
                ->first();

            if ($branch?->status !== 'active') {
                throw ValidationException::withMessages([
                    'branch_id' => 'Nhân viên bắt buộc thuộc một cơ sở đang hoạt động.',
                ]);
            }

            $staff->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'] ?? $staff->role,
                'branch_id' => $branch->getKey(),
            ]);
        });

        return redirect()->route('hr.staff.index')->with('status', 'Đã cập nhật nhân viên.');
    }

    public function lock(User $staff): RedirectResponse
    {
        $this->authorize('lock', $staff);

        $staff->update(['status' => 'locked']);

        return redirect()->route('hr.staff.index')->with('status', 'Đã khóa tài khoản.');
    }

    public function unlock(User $staff): RedirectResponse
    {
        $this->authorize('unlock', $staff);

        $staff->update(['status' => 'active']);

        return redirect()->route('hr.staff.index')->with('status', 'Đã mở khóa tài khoản.');
    }

    public function resetPassword(ResetStaffPasswordRequest $request, User $staff): RedirectResponse
    {
        (new ResetStaffPasswordAction)->handle($staff, $request->validated('password'));

        return redirect()->route('hr.dashboard')
            ->with('status', 'Đã đặt lại mật khẩu tạm cho nhân viên.');
    }

    protected function manageableBranches(): Collection
    {
        $actor = auth()->user();

        return Branch::query()
            ->where('status', 'active')
            ->when($actor->isBranchAdmin(), fn ($query) => $query->whereKey($actor->branch_id))
            ->orderBy('name')
            ->get();
    }
}
