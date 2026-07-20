<?php

namespace Tests\Feature\Foundation;

use App\Models\AdministrativeUnit;
use App\Models\IndustrialPark;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndustrialParkTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_administrative_unit(): void
    {
        $unit = AdministrativeUnit::factory()->create();
        $park = IndustrialPark::factory()->create(['administrative_unit_id' => $unit->id]);

        $this->assertTrue($park->administrativeUnit->is($unit));
        $this->assertTrue($unit->industrialParks->contains($park));
    }

    public function test_administrative_unit_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        IndustrialPark::factory()->create(['administrative_unit_id' => null]);
    }

    public function test_administrative_unit_id_must_reference_existing_unit(): void
    {
        $this->expectException(QueryException::class);

        IndustrialPark::factory()->create(['administrative_unit_id' => 999999]);
    }

    public function test_slug_must_be_unique_within_the_same_administrative_unit(): void
    {
        $unit = AdministrativeUnit::factory()->create();
        IndustrialPark::factory()->create(['administrative_unit_id' => $unit->id, 'slug' => 'khu-a']);

        $this->expectException(QueryException::class);

        IndustrialPark::factory()->create(['administrative_unit_id' => $unit->id, 'slug' => 'khu-a']);
    }

    public function test_same_slug_allowed_under_different_administrative_units(): void
    {
        $unitA = AdministrativeUnit::factory()->create();
        $unitB = AdministrativeUnit::factory()->create();

        $parkA = IndustrialPark::factory()->create(['administrative_unit_id' => $unitA->id, 'slug' => 'khu-a']);
        $parkB = IndustrialPark::factory()->create(['administrative_unit_id' => $unitB->id, 'slug' => 'khu-a']);

        $this->assertNotEquals($parkA->id, $parkB->id);
        $this->assertDatabaseHas('industrial_parks', ['id' => $parkA->id, 'slug' => 'khu-a']);
        $this->assertDatabaseHas('industrial_parks', ['id' => $parkB->id, 'slug' => 'khu-a']);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $park = IndustrialPark::factory()->create();

        $this->assertTrue($park->is_active);
    }

    public function test_deactivating_is_the_documented_removal_mechanism(): void
    {
        $park = IndustrialPark::factory()->create();

        $park->update(['is_active' => false]);

        $this->assertFalse($park->fresh()->is_active);
        $this->assertDatabaseHas('industrial_parks', ['id' => $park->id]);
    }

    public function test_cannot_hard_delete_administrative_unit_referenced_by_industrial_park(): void
    {
        $unit = AdministrativeUnit::factory()->create();
        IndustrialPark::factory()->create(['administrative_unit_id' => $unit->id]);

        $this->expectException(QueryException::class);

        $unit->delete();
    }

    public function test_industrial_park_can_be_hard_deleted_when_unreferenced(): void
    {
        $park = IndustrialPark::factory()->create();

        $park->delete();

        $this->assertDatabaseMissing('industrial_parks', ['id' => $park->id]);
    }
}
