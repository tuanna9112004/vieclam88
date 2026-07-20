<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetStaffPasswordAction
{
    /**
     * ADR-067 điểm 5: đặt mật khẩu tạm mới cho Staff, password_changed_at=null buộc
     * đổi lại ở lần đăng nhập/request kế tiếp (EnsurePasswordChanged tự chặn). Không
     * invalidate session hiện tại của Staff — Phase 1 chấp nhận độ trễ.
     */
    public function handle(User $staff, string $password): User
    {
        $staff->update([
            'password' => Hash::make($password),
            'password_changed_at' => null,
        ]);

        return $staff;
    }
}
