<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateStaffAction
{
    /**
     * Tạo Staff mới. Dictionary 9.1: role=staff bắt buộc branch_id — chốt ở tầng
     * Service (không ép NOT NULL ở DB vì admin không cần). Mật khẩu tạm do Admin
     * đặt, password_changed_at=null bắt buộc đổi ở lần đăng nhập đầu (ADR-067).
     *
     * @param  array{name: string, email: string, branch_id: int, password: string}  $data
     */
    public function handle(array $data): User
    {
        if (empty($data['branch_id'])) {
            throw ValidationException::withMessages([
                'branch_id' => 'Staff bắt buộc thuộc một cơ sở.',
            ]);
        }

        return User::create([
            'role' => 'staff',
            'branch_id' => $data['branch_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => 'active',
            'password_changed_at' => null,
        ]);
    }
}
