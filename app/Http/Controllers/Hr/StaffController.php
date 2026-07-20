<?php

namespace App\Http\Controllers\Hr;

use App\Actions\User\ResetStaffPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Staff\ResetStaffPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class StaffController extends Controller
{
    public function resetPassword(ResetStaffPasswordRequest $request, User $staff): RedirectResponse
    {
        (new ResetStaffPasswordAction)->handle($staff, $request->validated('password'));

        return redirect()->route('hr.dashboard')
            ->with('status', 'Đã đặt lại mật khẩu tạm cho nhân viên.');
    }
}
