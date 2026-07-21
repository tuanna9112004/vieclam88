<?php

namespace App\Models;

use App\Enums\JobCloseReason;
use App\Enums\JobEmploymentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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

    public function jobWorkShifts(): HasMany
    {
        return $this->hasMany(JobWorkShift::class);
    }

    public function jobVerifications(): HasMany
    {
        return $this->hasMany(JobVerification::class);
    }

    public function jobStatusHistories(): HasMany
    {
        return $this->hasMany(JobStatusHistory::class);
    }

    public function jobBranchHistories(): HasMany
    {
        return $this->hasMany(JobBranchHistory::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Giá trị tính toán tầng ứng dụng, không phải cột DB (ADR-072, `docs/CORE-FLOWS.md` mục 2.2):
     * `jobs.status` giữ nguyên `published` cho tới khi có hành động tường minh pause/close.
     */
    public function effectiveStatus(): string
    {
        if ($this->status === 'published' && $this->expires_at !== null && $this->expires_at->isPast()) {
            return 'expired';
        }

        return $this->status;
    }

    /**
     * Job còn nhận Application ("Job còn active" — `docs/CORE-FLOWS.md` mục 3): published, chưa
     * xóa, chưa hết hạn. Dùng chung cho hiển thị CTA/form ứng tuyển ở trang chi tiết public.
     */
    public function isOpenForApplication(): bool
    {
        return $this->effectiveStatus() === 'published';
    }

    /**
     * Company Contact công khai (`docs/CORE-FLOWS.md` mục 1, `.claude/rules/job-domain.md`):
     * chỉ hiển thị khi cùng company_id, active, chưa xóa và is_public=true — không bao giờ suy
     * ra hoặc mặc định lộ contact không đạt đủ 4 điều kiện.
     */
    public function publicCompanyContact(): ?CompanyContact
    {
        $contact = $this->companyContact;

        if (! $contact || $contact->company_id !== $this->company_id) {
            return null;
        }

        if (! $contact->is_public || $contact->status?->value !== 'active') {
            return null;
        }

        return $contact;
    }

    /**
     * Hiển thị lương public (list + chi tiết) — "Thỏa thuận" khi salary_period=negotiable hoặc
     * không có cả salary_min lẫn salary_max.
     */
    public function formattedSalary(): string
    {
        if ($this->salary_period === 'negotiable' || (! $this->salary_min && ! $this->salary_max)) {
            return 'Thỏa thuận';
        }

        $unit = match ($this->salary_period) {
            'day' => '/ngày',
            'hour' => '/giờ',
            'piece' => '/sản phẩm',
            default => '/tháng',
        };

        $format = fn (int $value) => number_format($value / 1_000_000, 1, ',', '.').' triệu';

        if ($this->salary_min && $this->salary_max) {
            return $format($this->salary_min).' - '.$format($this->salary_max).' '.$unit;
        }

        return $format((int) ($this->salary_min ?? $this->salary_max)).' '.$unit;
    }

    /**
     * Danh sách/tìm kiếm public (`docs/CORE-FLOWS.md` mục 2, `docs/ACCEPTANCE-CRITERIA.md` mục 7):
     * chỉ Job published, chưa hết hạn — soft delete đã bị loại tự động bởi SoftDeletes.
     */
    public function scopePubliclyListed(Builder $query): Builder
    {
        return $query->published()->notExpired();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
        });
    }

    public function scopeInCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeSearchKeyword(Builder $query, string $keyword): Builder
    {
        return $query->where('title', 'like', '%'.$keyword.'%');
    }

    /**
     * Chỉ khớp qua company_location đang active và (nếu áp dụng) KCN/ca đang active — "quan hệ
     * active hợp lệ" trong contract danh sách public.
     */
    public function scopeInIndustrialPark(Builder $query, int $industrialParkId): Builder
    {
        return $query->whereHas('jobLocations.companyLocation', function (Builder $q) use ($industrialParkId) {
            $q->where('status', 'active')
                ->where('industrial_park_id', $industrialParkId)
                ->whereHas('industrialPark', fn (Builder $ip) => $ip->where('is_active', true));
        });
    }

    public function scopeInAdministrativeUnit(Builder $query, int $administrativeUnitId): Builder
    {
        return $query->whereHas('jobLocations.companyLocation', function (Builder $q) use ($administrativeUnitId) {
            $q->where('status', 'active')->where('administrative_unit_id', $administrativeUnitId);
        });
    }

    public function scopeWithWorkShift(Builder $query, int $workShiftId): Builder
    {
        return $query->whereHas('jobWorkShifts', function (Builder $q) use ($workShiftId) {
            $q->where('work_shift_id', $workShiftId)
                ->whereHas('workShift', fn (Builder $ws) => $ws->where('is_active', true));
        });
    }

    public function scopeHasShuttleBus(Builder $query): Builder
    {
        return $query->where('has_shuttle_bus', true);
    }

    public function scopeHasAccommodation(Builder $query): Builder
    {
        return $query->where('has_accommodation', true);
    }

    /**
     * Bucket lương khớp UI tham khảo (`docs/ui-reference/phase-1/05.3-bo-loc-viec-lam.png`);
     * so khớp theo giao khoảng [min,max] trên salary_min/salary_max — NULL ở một đầu coi như
     * không giới hạn đầu đó, nhưng Job không có cả 2 (thực chất "thỏa thuận") không khớp bucket
     * số cụ thể nào.
     */
    public function scopeSalaryBucket(Builder $query, string $bucket): Builder
    {
        if ($bucket === 'thoa-thuan') {
            return $query->where('salary_period', 'negotiable');
        }

        [$min, $max] = self::SALARY_BUCKETS[$bucket] ?? [null, null];

        return $query
            ->where(fn (Builder $q) => $q->whereNotNull('salary_min')->orWhereNotNull('salary_max'))
            ->where(function (Builder $q) use ($min, $max) {
                if ($min !== null) {
                    $q->where(fn (Builder $qq) => $qq->whereNull('salary_max')->orWhere('salary_max', '>=', $min));
                }
                if ($max !== null) {
                    $q->where(fn (Builder $qq) => $qq->whereNull('salary_min')->orWhere('salary_min', '<=', $max));
                }
            });
    }

    public function scopeSortListing(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'salary_desc' => $query->orderByDesc('salary_max')->orderByDesc('salary_min'),
            'urgent' => $query->orderByDesc('is_urgent')->orderByDesc('published_at'),
            default => $query->orderByDesc('published_at'),
        };
    }

    /** @var array<string, array{0: int|null, 1: int|null}> */
    public const SALARY_BUCKETS = [
        'duoi-10' => [null, 10_000_000],
        '10-15' => [10_000_000, 15_000_000],
        '15-20' => [15_000_000, 20_000_000],
        '20-30' => [20_000_000, 30_000_000],
        '30-50' => [30_000_000, 50_000_000],
        'tren-50' => [50_000_000, null],
    ];
}
