<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'type', 'is_active', 'sort_order'])]
class RecruitmentSource extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => 'string',
            'is_active' => 'boolean',
        ];
    }
}
