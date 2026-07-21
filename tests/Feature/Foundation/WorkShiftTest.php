<?php

namespace Tests\Feature\Foundation;

use App\Models\WorkShift;
use Database\Seeders\WorkShiftSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkShiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_code_must_be_unique(): void
    {
        WorkShift::factory()->create(['code' => 'dup-code']);

        $this->expectException(QueryException::class);

        WorkShift::factory()->create(['code' => 'dup-code']);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $shift = WorkShift::factory()->create();

        $this->assertTrue($shift->fresh()->is_active);
    }

    public function test_seeder_creates_all_required_codes(): void
    {
        (new WorkShiftSeeder())->run();

        $codes = WorkShift::pluck('code')->all();

        foreach (['day', 'night', 'rotating', 'two_shift', 'three_shift', 'administrative', 'flexible'] as $expected) {
            $this->assertContains($expected, $codes);
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        (new WorkShiftSeeder())->run();
        (new WorkShiftSeeder())->run();

        $this->assertSame(7, WorkShift::count());
    }
}
