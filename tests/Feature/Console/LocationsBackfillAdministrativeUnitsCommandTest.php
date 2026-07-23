<?php

namespace Tests\Feature\Console;

use App\Models\AdministrativeUnit;
use App\Models\AdministrativeUnitMapping;
use App\Models\Province;
use App\Models\Ward;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Đếm/assert luôn scope theo administrative_unit_id do chính test tạo, không tin
 * AdministrativeUnit::count()/AdministrativeUnitMapping::count() toàn bảng — cùng lý do
 * ImportAdministrativeUnitsCommandTest scope theo official_code: administrative_units có nhiều
 * factory nested (Branch/IndustrialPark/CompanyLocation) tạo bản ghi phụ trong các test khác.
 */
class LocationsBackfillAdministrativeUnitsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/reports'));

        parent::tearDown();
    }

    private function latestReport(bool $dryRun = false): array
    {
        $files = collect(File::glob(storage_path('app/reports/administrative-unit-mapping-*.json')))
            ->filter(fn (string $path): bool => $dryRun === str_contains($path, '_dryrun'))
            ->sort()
            ->values();

        return json_decode(File::get($files->last()), true);
    }

    public function test_maps_root_and_leaf_units_correctly_via_official_code(): void
    {
        $hanoi = AdministrativeUnit::factory()->create(['official_code' => '1', 'type' => 'city', 'parent_id' => null]);
        $leafUnit = AdministrativeUnit::factory()->create([
            'official_code' => '4', 'type' => 'ward', 'parent_id' => $hanoi->id,
        ]);

        $hanoiProvince = Province::factory()->create(['code' => '1']);
        $baDinhWard = Ward::factory()->create(['code' => '4', 'province_id' => $hanoiProvince->id]);

        $exitCode = Artisan::call('locations:backfill-administrative-units');

        $this->assertSame(Command::SUCCESS, $exitCode);

        $rootMapping = AdministrativeUnitMapping::where('administrative_unit_id', $hanoi->id)->firstOrFail();
        $this->assertSame('mapped', $rootMapping->status);
        $this->assertSame($hanoiProvince->id, $rootMapping->province_id);
        $this->assertNull($rootMapping->ward_id);
        $this->assertNotNull($rootMapping->mapped_at);

        $leafMapping = AdministrativeUnitMapping::where('administrative_unit_id', $leafUnit->id)->firstOrFail();
        $this->assertSame('mapped', $leafMapping->status);
        $this->assertSame($hanoiProvince->id, $leafMapping->province_id);
        $this->assertSame($baDinhWard->id, $leafMapping->ward_id);
    }

    public function test_legacy_district_and_missing_official_code_are_classified_missing_without_guessing(): void
    {
        $province = AdministrativeUnit::factory()->create(['official_code' => '1', 'type' => 'city', 'parent_id' => null]);
        Province::factory()->create(['code' => '1']);

        $legacyDistrict = AdministrativeUnit::factory()->create([
            'official_code' => '999', 'type' => 'legacy_district', 'parent_id' => $province->id,
        ]);
        $noCode = AdministrativeUnit::factory()->create([
            'official_code' => null, 'type' => 'ward', 'parent_id' => $province->id,
        ]);

        Artisan::call('locations:backfill-administrative-units');

        $legacyMapping = AdministrativeUnitMapping::where('administrative_unit_id', $legacyDistrict->id)->firstOrFail();
        $this->assertSame('missing', $legacyMapping->status);
        $this->assertNull($legacyMapping->ward_id);
        $this->assertStringContainsString('legacy_district', $legacyMapping->reason);

        $noCodeMapping = AdministrativeUnitMapping::where('administrative_unit_id', $noCode->id)->firstOrFail();
        $this->assertSame('missing', $noCodeMapping->status);
        $this->assertNull($noCodeMapping->ward_id);
    }

    public function test_leaf_matching_ward_from_a_different_province_is_invalid_parent_not_guessed(): void
    {
        $hanoi = AdministrativeUnit::factory()->create(['official_code' => '1', 'type' => 'city', 'parent_id' => null]);
        $leaf = AdministrativeUnit::factory()->create(['official_code' => '4', 'type' => 'ward', 'parent_id' => $hanoi->id]);

        Province::factory()->create(['code' => '1']);
        // Ward "4" thực tế thuộc một province khác (code '27') trong dữ liệu mới — lệch với cha cũ.
        $otherProvince = Province::factory()->create(['code' => '27']);
        Ward::factory()->create(['code' => '4', 'province_id' => $otherProvince->id]);

        Artisan::call('locations:backfill-administrative-units');

        $mapping = AdministrativeUnitMapping::where('administrative_unit_id', $leaf->id)->firstOrFail();
        $this->assertSame('invalid_parent', $mapping->status);
        $this->assertNull($mapping->province_id);
        $this->assertNull($mapping->ward_id);
    }

    public function test_leaf_unit_with_no_parent_is_invalid_parent_not_mapped(): void
    {
        // Dữ liệu cũ bất thường: type lá nhưng parent_id=null (không có root để xác nhận province).
        $orphanLeaf = AdministrativeUnit::factory()->create(['official_code' => '4', 'type' => 'ward', 'parent_id' => null]);

        $province = Province::factory()->create(['code' => '1']);
        Ward::factory()->create(['code' => '4', 'province_id' => $province->id]);

        Artisan::call('locations:backfill-administrative-units');

        $mapping = AdministrativeUnitMapping::where('administrative_unit_id', $orphanLeaf->id)->firstOrFail();
        $this->assertSame('invalid_parent', $mapping->status);
        $this->assertNull($mapping->province_id);
        $this->assertNull($mapping->ward_id);
    }

    public function test_leaf_unit_with_cyclic_parent_chain_is_invalid_parent_not_mapped(): void
    {
        $unitA = AdministrativeUnit::factory()->create(['official_code' => '1', 'type' => 'city', 'parent_id' => null]);
        $unitB = AdministrativeUnit::factory()->create(['official_code' => '4', 'type' => 'ward', 'parent_id' => $unitA->id]);
        // Tạo vòng lặp trực tiếp trong dữ liệu (bypass action upsert để giả lập dữ liệu cũ hỏng).
        $unitA->forceFill(['parent_id' => $unitB->id])->saveQuietly();

        Province::factory()->create(['code' => '1']);
        $otherProvince = Province::factory()->create(['code' => '99']);
        Ward::factory()->create(['code' => '4', 'province_id' => $otherProvince->id]);

        Artisan::call('locations:backfill-administrative-units');

        $mapping = AdministrativeUnitMapping::where('administrative_unit_id', $unitB->id)->firstOrFail();
        $this->assertSame('invalid_parent', $mapping->status);
        $this->assertNull($mapping->province_id);
        $this->assertNull($mapping->ward_id);
    }

    public function test_root_type_matching_ward_code_instead_of_province_code_is_ambiguous(): void
    {
        $unit = AdministrativeUnit::factory()->create(['official_code' => '4', 'type' => 'province', 'parent_id' => null]);

        $someProvince = Province::factory()->create(['code' => '1']);
        Ward::factory()->create(['code' => '4', 'province_id' => $someProvince->id]);

        Artisan::call('locations:backfill-administrative-units');

        $mapping = AdministrativeUnitMapping::where('administrative_unit_id', $unit->id)->firstOrFail();
        $this->assertSame('ambiguous', $mapping->status);
    }

    public function test_dry_run_does_not_write_to_database_but_still_writes_report(): void
    {
        $unit = AdministrativeUnit::factory()->create(['official_code' => '1', 'type' => 'city', 'parent_id' => null]);
        Province::factory()->create(['code' => '1']);
        $totalBeforeRun = AdministrativeUnit::count();

        $exitCode = Artisan::call('locations:backfill-administrative-units', ['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFalse(AdministrativeUnitMapping::where('administrative_unit_id', $unit->id)->exists());
        $this->assertSame(0, AdministrativeUnitMapping::count(), 'Dry-run không được ghi bất kỳ dòng nào vào administrative_unit_mappings');

        $report = $this->latestReport(dryRun: true);
        $this->assertTrue($report['dry_run']);
        $this->assertSame($totalBeforeRun, $report['total']);
        $this->assertSame($totalBeforeRun, $report['counts']['mapped'] + $report['counts']['ambiguous'] + $report['counts']['missing'] + $report['counts']['invalid_parent']);
    }

    public function test_running_twice_is_idempotent_and_does_not_duplicate_mapping_rows(): void
    {
        $unit = AdministrativeUnit::factory()->create(['official_code' => '1', 'type' => 'city', 'parent_id' => null]);
        Province::factory()->create(['code' => '1']);

        Artisan::call('locations:backfill-administrative-units');
        $firstId = AdministrativeUnitMapping::where('administrative_unit_id', $unit->id)->value('id');

        Artisan::call('locations:backfill-administrative-units');

        $this->assertSame(1, AdministrativeUnitMapping::where('administrative_unit_id', $unit->id)->count());
        $this->assertSame($firstId, AdministrativeUnitMapping::where('administrative_unit_id', $unit->id)->value('id'));
    }

    public function test_resume_skips_already_mapped_records(): void
    {
        $mappedUnit = AdministrativeUnit::factory()->create(['official_code' => '1', 'type' => 'city', 'parent_id' => null]);
        $province = Province::factory()->create(['code' => '1']);

        AdministrativeUnitMapping::factory()->create([
            'administrative_unit_id' => $mappedUnit->id,
            'province_id' => $province->id,
            'status' => 'mapped',
            'mapped_at' => now()->subDay(),
        ]);
        $originalUpdatedAt = (string) AdministrativeUnitMapping::where('administrative_unit_id', $mappedUnit->id)->value('updated_at');

        $newUnit = AdministrativeUnit::factory()->create(['official_code' => null, 'type' => 'ward', 'parent_id' => $mappedUnit->id]);

        $exitCode = Artisan::call('locations:backfill-administrative-units', ['--resume' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame(
            $originalUpdatedAt,
            (string) AdministrativeUnitMapping::where('administrative_unit_id', $mappedUnit->id)->value('updated_at'),
            'Bản ghi đã mapped không được ghi lại khi --resume'
        );
        $this->assertSame('missing', AdministrativeUnitMapping::where('administrative_unit_id', $newUnit->id)->value('status'));
    }

    public function test_mapped_plus_unresolved_equals_total_scanned_in_report(): void
    {
        $province = AdministrativeUnit::factory()->create(['official_code' => '1', 'type' => 'city', 'parent_id' => null]);
        AdministrativeUnit::factory()->create(['official_code' => null, 'type' => 'ward', 'parent_id' => $province->id]);
        AdministrativeUnit::factory()->create(['official_code' => '999', 'type' => 'legacy_district', 'parent_id' => $province->id]);
        Province::factory()->create(['code' => '1']);
        $totalBeforeRun = AdministrativeUnit::count();

        Artisan::call('locations:backfill-administrative-units');

        $report = $this->latestReport();
        $unresolvedCount = $report['counts']['ambiguous'] + $report['counts']['missing'] + $report['counts']['invalid_parent'];
        $this->assertSame($totalBeforeRun, $report['total']);
        $this->assertSame($report['total'], $report['counts']['mapped'] + $unresolvedCount);
        $this->assertCount($unresolvedCount, $report['unresolved']);
        // Ít nhất 2 bản ghi missing do chính test này tạo (legacy_district + official_code rỗng).
        $this->assertGreaterThanOrEqual(2, $report['counts']['missing']);
    }

    public function test_batch_size_one_produces_same_result_as_default(): void
    {
        $province = AdministrativeUnit::factory()->create(['official_code' => '1', 'type' => 'city', 'parent_id' => null]);
        $leaf = AdministrativeUnit::factory()->create(['official_code' => '4', 'type' => 'ward', 'parent_id' => $province->id]);
        $provinceRow = Province::factory()->create(['code' => '1']);
        Ward::factory()->create(['code' => '4', 'province_id' => $provinceRow->id]);

        $exitCode = Artisan::call('locations:backfill-administrative-units', ['--batch-size' => 1]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame(
            2,
            AdministrativeUnitMapping::whereIn('administrative_unit_id', [$province->id, $leaf->id])
                ->where('status', 'mapped')
                ->count()
        );
    }
}
