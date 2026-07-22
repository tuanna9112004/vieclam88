<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    /**
     * hr.applications.index (docs/ROUTE-MAP.md muc "HR ho so"): moi Staff/Admin da dang nhap
     * deu xem duoc trang danh sach — pham vi Branch (Staff chi thay co so minh) duoc scope o
     * query trong Controller, khong phai o day (cung pattern voi JobPolicy::viewAny).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * hr.applications.show (docs/ROUTE-MAP.md muc "HR ho so"): cung quy tac Branch Policy —
     * Staff thuoc dung co so cua Application hoac Admin.
     */
    public function view(User $user, Application $application): bool
    {
        return $user->isAdmin() || $application->owner_branch_id === $user->branch_id;
    }

    /**
     * hr.applications.contacts.store (docs/CORE-FLOWS.md muc 4): Staff thuoc dung co so cua
     * Application hoac Admin — khong co khai niem "phan cong", bat ky staff nao cung co so deu
     * ghi duoc Contact Log cho moi Application cua co so do.
     */
    public function recordContact(User $user, Application $application): bool
    {
        return $user->isAdmin() || $application->owner_branch_id === $user->branch_id;
    }

    /**
     * hr.applications.appointments.store (docs/CORE-FLOWS.md muc 4, 5.3): cung quy tac voi
     * recordContact — Staff thuoc dung co so hoac Admin, khong co khai niem "phan cong".
     */
    public function scheduleAppointment(User $user, Application $application): bool
    {
        return $user->isAdmin() || $application->owner_branch_id === $user->branch_id;
    }

    /**
     * hr.applications.appointments.update (docs/CORE-FLOWS.md muc 5.3): danh dau hoan thanh/
     * huy/khong den — cung quy tac Branch Policy voi scheduleAppointment.
     */
    public function updateAppointment(User $user, Application $application): bool
    {
        return $user->isAdmin() || $application->owner_branch_id === $user->branch_id;
    }

    /**
     * hr.applications.stage (docs/CORE-FLOWS.md muc 4, 5.1): cung quy tac Branch Policy voi
     * recordContact/scheduleAppointment. Dieu kien rieng cho tung transition (vd mot so truong
     * hop reopen chi Admin) thuoc muc 5.5, chua trien khai o Action nay.
     */
    public function changeStage(User $user, Application $application): bool
    {
        return $user->isAdmin() || $application->owner_branch_id === $user->branch_id;
    }

    /**
     * hr.applications.transfer-branch (docs/CORE-FLOWS.md muc 6.1): chi Admin moi duoc chuyen co so.
     */
    public function transferBranch(User $user, Application $application): bool
    {
        return $user->isAdmin();
    }

    /**
     * hr.applications.export: Staff va Admin deu co quyen xuat CSV (Staff bi ranh buoc theo co so minh).
     */
    public function export(User $user): bool
    {
        return true;
    }
}
