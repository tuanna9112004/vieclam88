<?php

namespace Tests\Feature\Console;

use App\Models\AdministrativeUnit;
use App\Models\AdministrativeUnitMapping;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Province;
use App\Models\Ward;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LocationsBackfillWardFkCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/reports'));

        parent::tearDown();
    }

    private function latestReport(): array
    {
        $files = collect(File::glob(storage_path('app/reports/ward-fk-backfill-*.json')))
            ->sort()
            ->values();

        return json_decode(File::get($files->last()), true);
    }

    public function test_backfills_branch_ward_id_from_mapped_administrative_unit(): void
    {
        $unit = AdministrativeUnit::factory()->create();
        $province = Province::factory()->create();
        $ward = Ward::factory()->create(['province_id' => $province->id]);
        AdministrativeUnitMapping::factory()->create([
            'administrative_unit_id' => $unit->id,
            'province_id' => $province->id,
            'ward_id' => $ward->id,
            'status' => 'mapped',
        ]);
        $branch = Branch::factory()->create(['administrative_unit_id' => $unit->id, 'ward_id' => null]);

        $exitCode = Artisan::call('locations:backfill-ward-fk');

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame($ward->id, $branch->fresh()->ward_id);
    }

    public function test_backfills_candidate_current_ward_id_from_mapped_administrative_unit(): void
    {
        $unit = AdministrativeUnit::factory()->create();
        $ward = Ward::factory()->create();
        AdministrativeUnitMapping::factory()->create([
            'administrative_unit_id' => $unit->id,
            'ward_id' => $ward->id,
            'status' => 'mapped',
        ]);
        $candidate = Candidate::factory()->create([
            'current_administrative_unit_id' => $unit->id,
            'current_ward_id' => null,
        ]);

        Artisan::call('locations:backfill-ward-fk');

        $this->assertSame($ward->id, $candidate->fresh()->current_ward_id);
    }

    public function test_does_not_overwrite_ward_id_already_set(): void
    {
        $unit = AdministrativeUnit::factory()->create();
        $mappedWard = Ward::factory()->create();
        $manuallyChosenWard = Ward::factory()->create();
        AdministrativeUnitMapping::factory()->create([
            'administrative_unit_id' => $unit->id,
            'ward_id' => $mappedWard->id,
            'status' => 'mapped',
        ]);
        $branch = Branch::factory()->create([
            'administrative_unit_id' => $unit->id,
            'ward_id' => $manuallyChosenWard->id,
        ]);

        Artisan::call('locations:backfill-ward-fk');

        $this->assertSame($manuallyChosenWard->id, $branch->fresh()->ward_id);
    }

    public function test_dry_run_does_not_write_ward_id(): void
    {
        $unit = AdministrativeUnit::factory()->create();
        $ward = Ward::factory()->create();
        AdministrativeUnitMapping::factory()->create([
            'administrative_unit_id' => $unit->id,
            'ward_id' => $ward->id,
            'status' => 'mapped',
        ]);
        $branch = Branch::factory()->create(['administrative_unit_id' => $unit->id, 'ward_id' => null]);

        $exitCode = Artisan::call('locations:backfill-ward-fk', ['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertNull($branch->fresh()->ward_id);

        $report = $this->latestReport();
        $this->assertTrue($report['dry_run']);
        $this->assertSame(1, $report['tables']['branches']['updated']);
    }

    public function test_records_without_confirmed_mapping_are_reported_as_unresolved(): void
    {
        $unmappedUnit = AdministrativeUnit::factory()->create();
        $branch = Branch::factory()->create(['administrative_unit_id' => $unmappedUnit->id, 'ward_id' => null]);

        $ambiguousUnit = AdministrativeUnit::factory()->create();
        AdministrativeUnitMapping::factory()->create([
            'administrative_unit_id' => $ambiguousUnit->id,
            'status' => 'ambiguous',
            'ward_id' => null,
        ]);
        $secondBranch = Branch::factory()->create(['administrative_unit_id' => $ambiguousUnit->id, 'ward_id' => null]);

        Artisan::call('locations:backfill-ward-fk');

        $report = $this->latestReport();
        $unresolvedIds = collect($report['tables']['branches']['unresolved'])->pluck('id');
        $this->assertTrue($unresolvedIds->contains($branch->id));
        $this->assertTrue($unresolvedIds->contains($secondBranch->id));
        $this->assertNull($branch->fresh()->ward_id);
        $this->assertNull($secondBranch->fresh()->ward_id);
    }

    public function test_companies_are_intentionally_not_backfilled(): void
    {
        Artisan::call('locations:backfill-ward-fk');

        $report = $this->latestReport();
        $this->assertSame(0, $report['tables']['companies']['updated']);
        $this->assertArrayHasKey('note', $report['tables']['companies']);
    }

    public function test_running_twice_is_idempotent(): void
    {
        $unit = AdministrativeUnit::factory()->create();
        $ward = Ward::factory()->create();
        AdministrativeUnitMapping::factory()->create([
            'administrative_unit_id' => $unit->id,
            'ward_id' => $ward->id,
            'status' => 'mapped',
        ]);
        $branch = Branch::factory()->create(['administrative_unit_id' => $unit->id, 'ward_id' => null]);

        Artisan::call('locations:backfill-ward-fk');
        Artisan::call('locations:backfill-ward-fk');

        $this->assertSame($ward->id, $branch->fresh()->ward_id);
    }
}
