<?php

namespace App\Policies;

use App\Models\AdministrativeUnit;
use App\Models\User;

class AdministrativeUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * TASK 1.3: UI quản trị đơn vị hành chính cũ chuyển read-only/deprecated trong giai đoạn
     * chuyển tiếp sang provinces/wards — dữ liệu chỉ còn ghi qua administrative-units:import.
     * Không ai (kể cả Admin) được tạo/sửa qua HTTP nữa.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AdministrativeUnit $administrativeUnit): bool
    {
        return false;
    }
}
