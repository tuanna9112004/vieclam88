<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\User;

class ApplicationNotePolicy
{
    /**
     * hr.applications.notes.store (docs/ROUTE-MAP.md muc "HR ho so"): cung quy tac Branch Policy
     * voi ApplicationPolicy::recordContact — bat ky Staff nao cung co so cua Application deu them
     * duoc Note, khong rieng "nguoi tao".
     */
    public function create(User $user, Application $application): bool
    {
        return $user->isAdmin() || $application->owner_branch_id === $user->branch_id;
    }

    /**
     * hr.applications.notes.update/destroy (docs/CORE-FLOWS.md muc 7.3.1, ROUTE-MAP "note
     * creator hoac admin, dong thoi phai qua Application Branch Policy"): 2 lop dieu kien — chi
     * chinh nguoi tao Note (hoac Admin) MOI duoc sua/xoa, VA chi khi con thuoc dung branch hien
     * tai cua Application (Application co the bi transfer branch sau khi Note da tao — staff cu
     * mat quyen sua Note cua chinh minh khi do, dung quy tac chung voi moi du lieu khac cua
     * Application).
     */
    public function update(User $user, ApplicationNote $note): bool
    {
        return $this->isOwnerOrAdmin($user, $note) && $this->belongsToBranch($user, $note);
    }

    public function delete(User $user, ApplicationNote $note): bool
    {
        return $this->isOwnerOrAdmin($user, $note) && $this->belongsToBranch($user, $note);
    }

    private function isOwnerOrAdmin(User $user, ApplicationNote $note): bool
    {
        return $user->isAdmin() || $user->id === $note->user_id;
    }

    private function belongsToBranch(User $user, ApplicationNote $note): bool
    {
        return $user->isAdmin() || $note->application->owner_branch_id === $user->branch_id;
    }
}
