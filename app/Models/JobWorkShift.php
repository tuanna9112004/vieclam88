<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Khóa chính composite (job_id, work_shift_id), không có cột `id` riêng
 * (docs/DATABASE-DICTIONARY.md mục 9.12) — Eloquent không hỗ trợ PK dạng mảng nên tránh dùng
 * `find()`/`fresh()`/`refresh()` trên model này, thao tác qua query builder (`where(...)`).
 */
#[Fillable(['job_id', 'work_shift_id', 'description'])]
class JobWorkShift extends Model
{
    use HasFactory;

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'job_id';

    protected static function booted(): void
    {
        static::creating(function (JobWorkShift $jobWorkShift) {
            $jobWorkShift->created_at ??= now();
        });
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }
}
