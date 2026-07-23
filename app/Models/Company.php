<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'public_id', 'name', 'slug', 'short_name', 'description', 'logo_path', 'cover_path',
    'industry', 'website', 'is_verified', 'status', 'created_by', 'updated_by', 'headquarters_ward_id',
])]
class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'status' => 'string',
        ];
    }

    /**
     * TASK 1.3: cột thêm trước (Expand), chưa backfill/chưa có form — xem TASK 5.1/5.2.
     */
    public function headquartersWard(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'headquarters_ward_id');
    }

    public function companyLocations(): HasMany
    {
        return $this->hasMany(CompanyLocation::class);
    }

    public function companyContacts(): HasMany
    {
        return $this->hasMany(CompanyContact::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /**
     * Application cua Company qua Job (hr.dashboard Companies breakdown — Muc 9.1):
     * companies -> jobs.company_id -> applications.job_id. Dung cho withCount('applications')
     * de dem dung so ho so, khong duoc nham voi so Job (bug cu: alias 'jobs as applications_count').
     */
    public function applications(): HasManyThrough
    {
        return $this->hasManyThrough(Application::class, Job::class, 'company_id', 'job_id');
    }
}
