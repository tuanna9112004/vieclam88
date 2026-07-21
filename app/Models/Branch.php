<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name', 'phone', 'phone_normalized', 'zalo', 'email', 'administrative_unit_id', 'address_detail', 'status'])]
class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function administrativeUnit(): BelongsTo
    {
        return $this->belongsTo(AdministrativeUnit::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'owner_branch_id');
    }
}
