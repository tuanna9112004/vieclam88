<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    /**
     * hr.jobs.* (docs/ROUTE-MAP.md): Staff và Admin đều tạo được. Sửa Job: Admin không giới
     * hạn, Staff chỉ sửa được Job thuộc đúng cơ sở mình (docs/CORE-FLOWS.md mục 1.1) — truy cập
     * trực tiếp URL Job thuộc cơ sở khác phải 403, không chỉ ẩn nút.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Job $job): bool
    {
        return $user->isAdmin() || $job->owner_branch_id === $user->branch_id;
    }

    /**
     * hr.jobs.publish (docs/CORE-FLOWS.md mục 1.1, 1.2 điều kiện 22): cùng quy tắc với update() —
     * Staff chỉ publish được Job cơ sở mình, Admin không giới hạn. Điều kiện nội dung (status,
     * company, location...) là predicate 422 do PublishJobAction xử lý, không phải authorization.
     */
    public function publish(User $user, Job $job): bool
    {
        return $user->isAdmin() || $job->owner_branch_id === $user->branch_id;
    }

    /**
     * hr.jobs.verify (docs/CORE-FLOWS.md mục 1.3.1, ADR-059): Job đã `closed` không nhận
     * verification mới qua route Staff/Admin thông thường — chặn ở đây (403) cho cả hai role,
     * không chỉ Staff.
     */
    public function verify(User $user, Job $job): bool
    {
        if ($job->status === 'closed') {
            return false;
        }

        return $user->isAdmin() || $job->owner_branch_id === $user->branch_id;
    }

    /**
     * hr.jobs.pause / hr.jobs.close (docs/CORE-FLOWS.md mục 1.1 — Job Authorization Matrix):
     * cùng quy tắc với update()/publish() — Staff chỉ thao tác Job cơ sở mình, Admin không giới
     * hạn. Transition hợp lệ (published->paused, published|paused->closed) do
     * ChangeJobStatusAction xử lý, không phải authorization.
     */
    public function pause(User $user, Job $job): bool
    {
        return $user->isAdmin() || $job->owner_branch_id === $user->branch_id;
    }

    public function close(User $user, Job $job): bool
    {
        return $user->isAdmin() || $job->owner_branch_id === $user->branch_id;
    }

    /**
     * hr.jobs.transfer-branch (docs/CORE-FLOWS.md mục 1.1, ADR-054): chỉ Admin — Staff không có
     * quyền/route đổi owner_branch_id dưới bất kỳ hình thức nào, kể cả Job cơ sở mình.
     */
    public function transferBranch(User $user, Job $job): bool
    {
        return $user->isAdmin();
    }
}
