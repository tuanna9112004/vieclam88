<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['exported_by', 'export_type', 'filters', 'row_count', 'file_name'])]
class ExportLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'created_at' => 'datetime',
            'row_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ExportLog $log) {
            $log->created_at ??= now();
        });
    }

    public function exportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exported_by');
    }
}
