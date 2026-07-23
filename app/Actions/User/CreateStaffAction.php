<?php

namespace App\Actions\User;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateStaffAction
{
    /**
     * Tạo Staff/Branch Admin mới. Hai role này bắt buộc branch_id — chốt ở tầng
     * Service (không ép NOT NULL ở DB vì super_admin không cần). Mật khẩu tạm do quản trị viên
     * đặt, password_changed_at=null bắt buộc đổi ở lần đăng nhập đầu (ADR-067).
     *
     * @param  array{name: string, email: string, branch_id: int, password: string, role?: string}  $data
     */
    public function handle(array $data, User $actor): User
    {
        Gate::forUser($actor)->authorize('create', User::class);

        $branchId = $actor->isBranchAdmin()
            ? $actor->branch_id
            : ($data['branch_id'] ?? null);
        $role = $actor->isSuperAdmin()
            ? ($data['role'] ?? 'staff')
            : 'staff';

        if (! in_array($role, ['staff', 'branch_admin'], true)) {
            throw ValidationException::withMessages([
                'role' => 'Vai trò tài khoản không hợp lệ.',
            ]);
        }

        return DB::transaction(function () use ($branchId, $data, $role): User {
            $branch = $branchId !== null
                ? Branch::query()->whereKey($branchId)->lockForUpdate()->first()
                : null;

            if ($branch?->status !== 'active') {
                throw ValidationException::withMessages([
                    'branch_id' => 'Staff bắt buộc thuộc một cơ sở đang hoạt động.',
                ]);
            }

            return User::create([
                'role' => $role,
                'branch_id' => $branch->getKey(),
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => 'active',
                'password_changed_at' => null,
            ]);
        });
    }
}
