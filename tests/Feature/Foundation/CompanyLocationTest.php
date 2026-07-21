<?php

namespace Tests\Feature\Foundation;

use App\Models\AdministrativeUnit;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\IndustrialPark;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        CompanyLocation::factory()->create(['company_id' => null]);
    }

    public function test_deleting_company_referenced_by_location_is_restricted(): void
    {
        // Company::delete() la soft delete (khong cham FK) — dung forceDelete() de kiem tra
        // dung rang buoc RESTRICT o tang DB khi co hard-delete (vd can thiep DB thu cong).
        $company = Company::factory()->create();
        CompanyLocation::factory()->create(['company_id' => $company->id]);

        $this->expectException(QueryException::class);

        $company->forceDelete();
    }

    public function test_name_is_required(): void
    {
        $this->expectException(QueryException::class);

        CompanyLocation::factory()->create(['name' => null]);
    }

    public function test_administrative_unit_and_address_are_nullable_for_quick_create(): void
    {
        $location = CompanyLocation::factory()->minimal()->create(['name' => 'Nhà máy 1']);

        $this->assertNull($location->administrative_unit_id);
        $this->assertNull($location->address_detail);
        $this->assertDatabaseHas('company_locations', ['id' => $location->id, 'name' => 'Nhà máy 1']);
    }

    public function test_industrial_park_id_is_nullable(): void
    {
        $location = CompanyLocation::factory()->create(['industrial_park_id' => null]);

        $this->assertNull($location->fresh()->industrial_park_id);
    }

    public function test_deleting_administrative_unit_referenced_by_location_is_restricted(): void
    {
        $unit = AdministrativeUnit::factory()->create();
        CompanyLocation::factory()->create(['administrative_unit_id' => $unit->id]);

        $this->expectException(QueryException::class);

        $unit->delete();
    }

    public function test_deleting_industrial_park_referenced_by_location_is_restricted(): void
    {
        $park = IndustrialPark::factory()->create();
        CompanyLocation::factory()->create(['industrial_park_id' => $park->id]);

        $this->expectException(QueryException::class);

        $park->delete();
    }

    public function test_status_only_accepts_defined_values(): void
    {
        $this->expectException(QueryException::class);

        CompanyLocation::factory()->create(['status' => 'archived']);
    }

    public function test_status_defaults_to_active_when_not_specified(): void
    {
        // Insert thẳng qua query builder, không qua factory (factory luôn set 'status'), để
        // xác nhận DB tự điền default 'active' khi cột không được truyền trong câu insert.
        $company = Company::factory()->create();

        $id = DB::table('company_locations')->insertGetId([
            'company_id' => $company->id,
            'name' => 'Nhà máy mặc định',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('company_locations', ['id' => $id, 'status' => 'active']);
    }

    public function test_soft_delete_keeps_location_row(): void
    {
        $location = CompanyLocation::factory()->create();

        $location->delete();

        $this->assertSoftDeleted('company_locations', ['id' => $location->id]);
        $this->assertDatabaseHas('company_locations', ['id' => $location->id]);
    }

    public function test_restoring_a_soft_deleted_location(): void
    {
        $location = CompanyLocation::factory()->create();
        $location->delete();

        $location->restore();

        $this->assertDatabaseHas('company_locations', ['id' => $location->id, 'deleted_at' => null]);
    }

    public function test_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $location = CompanyLocation::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($location->company->is($company));
    }
}
