<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['role', 'branch_id', 'name', 'email', 'password', 'status', 'password_changed_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isBranchAdmin(): bool
    {
        return $this->role === 'branch_admin';
    }

    /**
     * @deprecated Dùng isSuperAdmin() cho code mới; giữ một release để tương thích.
     */
    public function isAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasValidBranchAssignment(): bool
    {
        if ($this->isSuperAdmin()) {
            return $this->branch_id === null;
        }

        if ((! $this->isBranchAdmin() && ! $this->isStaff()) || $this->branch_id === null) {
            return false;
        }

        $branch = $this->relationLoaded('branch')
            ? $this->branch
            : $this->branch()->first();

        return $branch?->status === 'active';
    }

    public function canManageBranch(Branch|int $branch): bool
    {
        if ($this->isSuperAdmin()) {
            return $this->branch_id === null;
        }

        $branchId = $branch instanceof Branch ? $branch->getKey() : $branch;

        return $this->isBranchAdmin()
            && $this->hasValidBranchAssignment()
            && $this->branch_id === $branchId;
    }
}
