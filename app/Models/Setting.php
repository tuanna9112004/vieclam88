<?php

namespace App\Models;

use App\Enums\SettingType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'type', 'group_name', 'is_public'])]
class Setting extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
            'is_public' => 'boolean',
        ];
    }
}
