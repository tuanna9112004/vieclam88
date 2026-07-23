<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bảng chuyển tiếp (TASK 1.2): ghi kết quả map một `administrative_units` sang
 * `provinces`/`wards` mới. Chỉ đọc/ghi bởi `locations:backfill-administrative-units`; không
 * cập nhật FK bảng nghiệp vụ (branches/companies/candidates — thuộc TASK 1.3).
 */
#[Fillable(['administrative_unit_id', 'province_id', 'ward_id', 'status', 'reason', 'mapped_at'])]
class AdministrativeUnitMapping extends Model
{
    use HasFactory;

    public const STATUSES = ['mapped', 'ambiguous', 'missing', 'invalid_parent'];

    protected function casts(): array
    {
        return [
            'mapped_at' => 'datetime',
        ];
    }

    public function administrativeUnit(): BelongsTo
    {
        return $this->belongsTo(AdministrativeUnit::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }
}
