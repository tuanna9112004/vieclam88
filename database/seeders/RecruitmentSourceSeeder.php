<?php

namespace Database\Seeders;

use App\Models\RecruitmentSource;
use Illuminate\Database\Seeder;

class RecruitmentSourceSeeder extends Seeder
{
    /**
     * docs/DATABASE-DICTIONARY.md muc 9.14 — seeder bat buoc, dung code -> type.
     */
    public function run(): void
    {
        $sources = [
            'website' => ['type' => 'website', 'name' => 'Website'],
            'zalo' => ['type' => 'zalo', 'name' => 'Zalo'],
            'facebook' => ['type' => 'social', 'name' => 'Facebook'],
            'staff' => ['type' => 'staff', 'name' => 'Nhân viên giới thiệu'],
            'referral' => ['type' => 'referral', 'name' => 'Người quen giới thiệu'],
            'other' => ['type' => 'other', 'name' => 'Khác'],
        ];

        foreach ($sources as $code => $attributes) {
            RecruitmentSource::query()->updateOrCreate(
                ['code' => $code],
                ['type' => $attributes['type'], 'name' => $attributes['name'], 'is_active' => true]
            );
        }
    }
}
