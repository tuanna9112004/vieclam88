<?php

namespace Tests\Feature\Hr\CompanyLocation;

use App\Models\AdministrativeUnit;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\IndustrialPark;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyLocationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_location_index(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $this->actingAs($admin)->get(route('hr.company-locations.index', $company))->assertOk();
    }

    public function test_staff_can_view_location_index(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($staff)->get(route('hr.company-locations.index', $company))->assertOk();
    }

    public function test_guest_is_redirected_from_location_index(): void
    {
        $company = Company::factory()->create();

        $this->get(route('hr.company-locations.index', $company))->assertRedirect(route('hr.login'));
    }

    public function test_staff_can_create_location_with_only_name(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($staff)->post(route('hr.company-locations.store', $company), [
            'name' => 'Nhà máy 1',
        ]);

        $response->assertRedirect(route('hr.company-locations.index', $company));
        $this->assertDatabaseHas('company_locations', [
            'company_id' => $company->id,
            'name' => 'Nhà máy 1',
            'administrative_unit_id' => null,
            'address_detail' => null,
        ]);
    }

    public function test_creating_location_requires_name(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($staff)->post(route('hr.company-locations.store', $company), []);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_location(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $location = CompanyLocation::factory()->minimal()->create(['company_id' => $company->id, 'name' => 'Ten cu']);
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->put(route('hr.company-locations.update', [$company, $location]), [
            'name' => 'Ten moi',
            'administrative_unit_id' => $unit->id,
        ]);

        $response->assertRedirect(route('hr.company-locations.index', $company));
        $location->refresh();
        $this->assertSame('Ten moi', $location->name);
        $this->assertSame($unit->id, $location->administrative_unit_id);
    }

    public function test_staff_cannot_delete_location(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();
        $location = CompanyLocation::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($staff)->delete(route('hr.company-locations.destroy', [$company, $location]));

        $response->assertForbidden();
        $this->assertDatabaseHas('company_locations', ['id' => $location->id, 'deleted_at' => null]);
    }

    public function test_staff_cannot_restore_location(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();
        $location = CompanyLocation::factory()->create(['company_id' => $company->id]);
        $location->delete();

        $response = $this->actingAs($staff)->post(route('hr.company-locations.restore', [$company, $location]));

        $response->assertForbidden();
        $this->assertSoftDeleted('company_locations', ['id' => $location->id]);
    }

    public function test_admin_can_delete_and_restore_location(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $location = CompanyLocation::factory()->create(['company_id' => $company->id]);

        $this->actingAs($admin)->delete(route('hr.company-locations.destroy', [$company, $location]))
            ->assertRedirect(route('hr.company-locations.index', $company));
        $this->assertSoftDeleted('company_locations', ['id' => $location->id]);

        $this->actingAs($admin)->post(route('hr.company-locations.restore', [$company, $location]))
            ->assertRedirect(route('hr.company-locations.index', $company));
        $this->assertDatabaseHas('company_locations', ['id' => $location->id, 'deleted_at' => null]);
    }

    public function test_creating_location_with_mismatched_province_and_industrial_park_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $unitA = AdministrativeUnit::factory()->create(['is_active' => true]);
        $unitB = AdministrativeUnit::factory()->create(['is_active' => true]);
        $park = IndustrialPark::factory()->create(['administrative_unit_id' => $unitA->id, 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('hr.company-locations.store', $company), [
            'name' => 'Nhà máy KCN',
            'administrative_unit_id' => $unitB->id,
            'industrial_park_id' => $park->id,
        ]);

        $response->assertSessionHasErrors('administrative_unit_id');
        $this->assertDatabaseMissing('company_locations', ['name' => 'Nhà máy KCN']);
    }

    public function test_creating_location_with_matching_province_and_industrial_park_succeeds(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);
        $park = IndustrialPark::factory()->create(['administrative_unit_id' => $unit->id, 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('hr.company-locations.store', $company), [
            'name' => 'Nhà máy KCN đúng tỉnh',
            'administrative_unit_id' => $unit->id,
            'industrial_park_id' => $park->id,
        ]);

        $response->assertRedirect(route('hr.company-locations.index', $company));
        $this->assertDatabaseHas('company_locations', [
            'name' => 'Nhà máy KCN đúng tỉnh',
            'administrative_unit_id' => $unit->id,
            'industrial_park_id' => $park->id,
        ]);
    }

    public function test_updating_location_with_mismatched_province_and_industrial_park_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $unitA = AdministrativeUnit::factory()->create(['is_active' => true]);
        $unitB = AdministrativeUnit::factory()->create(['is_active' => true]);
        $park = IndustrialPark::factory()->create(['administrative_unit_id' => $unitA->id, 'is_active' => true]);
        $location = CompanyLocation::factory()->create([
            'company_id' => $company->id,
            'administrative_unit_id' => $unitA->id,
            'industrial_park_id' => null,
        ]);

        $response = $this->actingAs($admin)->put(route('hr.company-locations.update', [$company, $location]), [
            'name' => $location->name,
            'administrative_unit_id' => $unitB->id,
            'industrial_park_id' => $park->id,
        ]);

        $response->assertSessionHasErrors('administrative_unit_id');
        $this->assertNull($location->fresh()->industrial_park_id);
    }

    public function test_location_from_different_company_returns_404_on_update(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $location = CompanyLocation::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->actingAs($admin)->put(route('hr.company-locations.update', [$company, $location]), [
            'name' => 'Bi doi ten sai',
        ]);

        $response->assertNotFound();
    }

    public function test_creating_location_requires_industrial_park_to_be_active(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);
        $park = IndustrialPark::factory()->create(['administrative_unit_id' => $unit->id, 'is_active' => false]);

        $response = $this->actingAs($admin)->post(route('hr.company-locations.store', $company), [
            'name' => 'Nhà máy KCN ngừng hoạt động',
            'administrative_unit_id' => $unit->id,
            'industrial_park_id' => $park->id,
        ]);

        $response->assertSessionHasErrors('industrial_park_id');
    }
}
