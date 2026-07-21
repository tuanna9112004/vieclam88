<?php

namespace App\Models;

use App\Enums\JobCloseReason;
use App\Enums\JobEmploymentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'public_id', 'company_id', 'company_contact_id', 'owner_branch_id', 'code', 'title', 'slug',
    'employment_type', 'quantity', 'gender_requirement', 'min_age', 'max_age',
    'education_requirement', 'experience_requirement', 'salary_min', 'salary_max', 'salary_base',
    'salary_period', 'currency', 'salary_description', 'job_description', 'requirements',
    'benefits', 'application_documents', 'has_shuttle_bus', 'shuttle_bus_details',
    'has_accommodation', 'accommodation_details', 'has_meal_support', 'meal_support_details',
    'is_urgent', 'status', 'published_at', 'expires_at', 'closed_at', 'close_reason',
    'last_checked_at', 'last_verified_at', 'created_by', 'updated_by', 'deleted_by',
])]
class Job extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'employment_type' => JobEmploymentType::class,
            'close_reason' => JobCloseReason::class,
            'status' => 'string',
            'gender_requirement' => 'string',
            'salary_period' => 'string',
            'has_shuttle_bus' => 'boolean',
            'has_accommodation' => 'boolean',
            'has_meal_support' => 'boolean',
            'is_urgent' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'closed_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'last_verified_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companyContact(): BelongsTo
    {
        return $this->belongsTo(CompanyContact::class);
    }

    public function ownerBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'owner_branch_id');
    }

    public function jobLocations(): HasMany
    {
        return $this->hasMany(JobLocation::class);
    }
}
