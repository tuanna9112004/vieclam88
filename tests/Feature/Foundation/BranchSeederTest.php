<?php

namespace Tests\Feature\Foundation;

use App\Models\Branch;
use App\Models\Ward;
use Database\Seeders\BranchSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BranchSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_four_canonical_branches_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $expected = [
            'VP' => 'Chi nhánh Vĩnh Phúc',
            'PT' => 'Văn phòng đại diện Phú Thọ',
            'HB' => 'Chi nhánh Hòa Bình',
            'BGBN' => 'Chi nhánh Bắc Giang - Bắc Ninh',
        ];

        $this->assertSame(4, Branch::query()->whereIn('code', array_keys($expected))->count());

        foreach ($expected as $code => $name) {
            $this->assertDatabaseHas('branches', [
                'code' => $code,
                'name' => $name,
                'status' => 'active',
                'administrative_unit_id' => null,
                'ward_id' => null,
                'deleted_at' => null,
            ]);
            $this->assertSame(1, Branch::query()->where('code', $code)->count());
        }
    }

    public function test_reseed_preserves_existing_inactive_status_cta_and_verified_address_fields(): void
    {
        $ward = Ward::factory()->create();
        $branch = Branch::factory()->create([
            'code' => 'VP',
            'name' => 'Tên cũ',
            'phone' => '0981000001',
            'phone_normalized' => '0981000001',
            'zalo' => '0981000002',
            'email' => 'vp@vieclam88.test',
            'ward_id' => $ward->id,
            'address_detail' => 'Địa chỉ đã xác minh',
            'status' => 'inactive',
        ]);

        $this->seed(BranchSeeder::class);

        $branch->refresh();

        $this->assertSame('Chi nhánh Vĩnh Phúc', $branch->name);
        $this->assertSame('inactive', $branch->status);
        $this->assertSame('0981000001', $branch->phone);
        $this->assertSame('0981000001', $branch->phone_normalized);
        $this->assertSame('0981000002', $branch->zalo);
        $this->assertSame('vp@vieclam88.test', $branch->email);
        $this->assertSame($ward->id, $branch->ward_id);
        $this->assertSame('Địa chỉ đã xác minh', $branch->address_detail);
    }

    public function test_reseed_restores_canonical_soft_deleted_branch_without_reactivating_it(): void
    {
        $branch = Branch::factory()->create([
            'code' => 'HB',
            'phone' => '0981000003',
            'status' => 'inactive',
        ]);
        $branch->delete();

        $this->seed(BranchSeeder::class);

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'code' => 'HB',
            'name' => 'Chi nhánh Hòa Bình',
            'phone' => '0981000003',
            'status' => 'inactive',
            'deleted_at' => null,
        ]);
    }

    public function test_reseed_canonicalizes_case_and_whitespace_in_existing_code(): void
    {
        $branch = Branch::factory()->create([
            'code' => 'vp ',
            'name' => 'Tên chưa chuẩn',
            'phone' => '0981000005',
        ]);

        $this->seed(BranchSeeder::class);

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'code' => 'VP',
            'name' => 'Chi nhánh Vĩnh Phúc',
            'phone' => '0981000005',
        ]);
        $this->assertSame(1, Branch::query()->whereRaw('UPPER(TRIM(code)) = ?', ['VP'])->count());
    }

    public function test_legacy_branches_are_preserved_and_reported_for_manual_merge(): void
    {
        $this->travelTo(Carbon::parse('2026-07-24 10:11:12'));

        $possibleDuplicate = Branch::factory()->create([
            'code' => 'BR-BG-01',
            'name' => 'Cơ sở Bắc Giang',
            'phone' => '0981000004',
        ]);
        $legacy = Branch::factory()->create([
            'code' => 'OLD-HN',
            'name' => '=HYPERLINK("https://example.test")',
        ]);

        $this->seed(BranchSeeder::class);

        $this->assertDatabaseHas('branches', ['id' => $possibleDuplicate->id, 'phone' => '0981000004']);
        $this->assertDatabaseHas('branches', ['id' => $legacy->id]);

        $jsonPath = storage_path('app/reports/branch-seed-duplicates-20260724_101112_000000.json');
        $csvPath = storage_path('app/reports/branch-seed-duplicates-20260724_101112_000000.csv');
        $this->assertFileExists($jsonPath);
        $this->assertFileExists($csvPath);

        $report = json_decode(File::get($jsonPath), true, flags: JSON_THROW_ON_ERROR);
        $byCode = collect($report['branches'])->keyBy('code');

        $this->assertSame(2, $report['counts']['legacy']);
        $this->assertSame(1, $report['counts']['possible_duplicates']);
        $this->assertSame('possible_duplicate', $byCode['BR-BG-01']['classification']);
        $this->assertSame('BGBN', $byCode['BR-BG-01']['possible_matches'][0]['code']);
        $this->assertSame('legacy', $byCode['OLD-HN']['classification']);
        $this->assertSame([], $byCode['OLD-HN']['possible_matches']);
        $this->assertStringContainsString('BR-BG-01', File::get($csvPath));
        $this->assertStringContainsString('"\'=HYPERLINK', File::get($csvPath));
    }
}
