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
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $staff = User::query()
            ->where('role', 'staff')
            ->with('branch')
            ->orderBy('name')
            ->paginate(20);

        return view('hr.staff.index', compact('staff'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        return view('hr.staff.create', compact('branches'));
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        (new CreateStaffAction)->handle($request->validated());

        return redirect()->route('hr.staff.index')->with('status', 'Đã tạo nhân viên.');
    }

    public function edit(User $staff): View
    {
        $this->authorize('update', $staff);

        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        return view('hr.staff.edit', compact('staff', 'branches'));
    }

    public function update(UpdateStaffRequest $request, User $staff): RedirectResponse
    {
        $staff->update([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'branch_id' => $request->validated('branch_id'),
        ]);

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
}
