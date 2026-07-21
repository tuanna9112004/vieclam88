<?php

namespace Database\Seeders;

use App\Models\WorkShift;
use Illuminate\Database\Seeder;

class WorkShiftSeeder extends Seeder
{
    /**
     * docs/DATABASE-DICTIONARY.md muc 9.11 — seeder bat buoc.
     */
    public function run(): void
    {
        $shifts = [
            'day' => 'Ca ngày',
            'night' => 'Ca đêm',
            'rotating' => 'Ca xoay',
            'two_shift' => '2 ca',
            'three_shift' => '3 ca',
            'administrative' => 'Giờ hành chính',
            'flexible' => 'Linh hoạt',
        ];

        foreach ($shifts as $code => $name) {
            WorkShift::query()->updateOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }
    }
}
