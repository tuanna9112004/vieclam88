<?php

namespace Tests\Feature\Foundation;

use App\Models\RecruitmentSource;
use Database\Seeders\RecruitmentSourceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitmentSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_code_must_be_unique(): void
    {
        RecruitmentSource::factory()->create(['code' => 'dup-code']);

        $this->expectException(QueryException::class);

        RecruitmentSource::factory()->create(['code' => 'dup-code']);
    }

    public function test_type_rejects_invalid_value(): void
    {
        $this->expectException(QueryException::class);

        RecruitmentSource::factory()->create(['type' => 'invalid_type']);
    }

    public function test_seeder_creates_correct_code_to_type_mapping(): void
    {
        (new RecruitmentSourceSeeder())->run();

        $this->assertDatabaseHas('recruitment_sources', ['code' => 'website', 'type' => 'website']);
        $this->assertDatabaseHas('recruitment_sources', ['code' => 'zalo', 'type' => 'zalo']);
        $this->assertDatabaseHas('recruitment_sources', ['code' => 'facebook', 'type' => 'social']);
        $this->assertDatabaseHas('recruitment_sources', ['code' => 'staff', 'type' => 'staff']);
        $this->assertDatabaseHas('recruitment_sources', ['code' => 'referral', 'type' => 'referral']);
        $this->assertDatabaseHas('recruitment_sources', ['code' => 'other', 'type' => 'other']);
    }

    public function test_seeder_is_idempotent(): void
    {
        (new RecruitmentSourceSeeder())->run();
        (new RecruitmentSourceSeeder())->run();

        $this->assertSame(6, RecruitmentSource::count());
    }
}
