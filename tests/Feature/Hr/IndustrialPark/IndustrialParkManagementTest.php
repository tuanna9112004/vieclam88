<?php

namespace Tests\Feature\Hr\IndustrialPark;

use App\Models\AdministrativeUnit;
use App\Models\IndustrialPark;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndustrialParkManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('hr.industrial-parks.index'))->assertOk();
    }

    public function test_staff_cannot_view_index(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('hr.industrial-parks.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('hr.industrial-parks.index'))->assertRedirect(route('hr.login'));
    }

    public function test_admin_can_create_industrial_park_under_active_administrative_unit(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->post(route('hr.industrial-parks.store'), [
            'administrative_unit_id' => $unit->id,
            'name' => 'Khu công nghiệp A',
            'official_name' => 'KCN A chính thức',
            'address_detail' => 'Số 1, đường ABC',
        ]);

        $response->assertRedirect(route('hr.industrial-parks.index'));

        $this->assertDatabaseHas('industrial_parks', [
            'administrative_unit_id' => $unit->id,
            'name' => 'Khu công nghiệp A',
            'slug' => 'khu-cong-nghiep-a',
            'is_active' => true,
        ]);
    }

    public function test_creating_industrial_park_requires_an_active_administrative_unit(): void
    {
        $admin = User::factory()->admin()->create();
        $inactiveUnit = AdministrativeUnit::factory()->create(['is_active' => false]);

        $response = $this->actingAs($admin)->post(route('hr.industrial-parks.store'), [
            'administrative_unit_id' => $inactiveUnit->id,
            'name' => 'Khu công nghiệp B',
        ]);

        $response->assertSessionHasErrors('administrative_unit_id');
        $this->assertDatabaseMissing('industrial_parks', ['name' => 'Khu công nghiệp B']);
    }

    public function test_creating_industrial_park_requires_a_name(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->post(route('hr.industrial-parks.store'), [
            'administrative_unit_id' => $unit->id,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_duplicate_names_in_same_administrative_unit_get_distinct_slugs(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->post(route('hr.industrial-parks.store'), [
            'administrative_unit_id' => $unit->id,
            'name' => 'Khu công nghiệp A',
        ]);

        $this->actingAs($admin)->post(route('hr.industrial-parks.store'), [
            'administrative_unit_id' => $unit->id,
            'name' => 'Khu công nghiệp A',
        ]);

        $this->assertDatabaseHas('industrial_parks', ['slug' => 'khu-cong-nghiep-a']);
        $this->assertDatabaseHas('industrial_parks', ['slug' => 'khu-cong-nghiep-a-2']);
    }

    public function test_staff_cannot_create_industrial_park(): void
    {
        $staff = User::factory()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);

        $response = $this->actingAs($staff)->post(route('hr.industrial-parks.store'), [
            'administrative_unit_id' => $unit->id,
            'name' => 'Khu công nghiệp A',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('industrial_parks', ['name' => 'Khu công nghiệp A']);
    }

    public function test_admin_can_update_industrial_park_and_toggle_inactive(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);
        $park = IndustrialPark::factory()->create(['administrative_unit_id' => $unit->id, 'is_active' => true]);

        $response = $this->actingAs($admin)->put(route('hr.industrial-parks.update', $park), [
            'administrative_unit_id' => $unit->id,
            'name' => 'Tên đã sửa',
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('hr.industrial-parks.index'));

        $fresh = $park->fresh();
        $this->assertSame('Tên đã sửa', $fresh->name);
        $this->assertFalse($fresh->is_active);
    }

    public function test_updating_industrial_park_requires_an_active_administrative_unit(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);
        $inactiveUnit = AdministrativeUnit::factory()->create(['is_active' => false]);
        $park = IndustrialPark::factory()->create(['administrative_unit_id' => $unit->id]);

        $response = $this->actingAs($admin)->put(route('hr.industrial-parks.update', $park), [
            'administrative_unit_id' => $inactiveUnit->id,
            'name' => $park->name,
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors('administrative_unit_id');
        $this->assertSame($unit->id, $park->fresh()->administrative_unit_id);
    }

    public function test_staff_cannot_update_industrial_park(): void
    {
        $staff = User::factory()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);
        $park = IndustrialPark::factory()->create(['administrative_unit_id' => $unit->id, 'name' => 'Ten goc']);

        $response = $this->actingAs($staff)->put(route('hr.industrial-parks.update', $park), [
            'administrative_unit_id' => $unit->id,
            'name' => 'Ten bi doi',
            'is_active' => '1',
        ]);

        $response->assertForbidden();
        $this->assertSame('Ten goc', $park->fresh()->name);
    }

    public function test_industrial_park_cannot_be_hard_deleted_via_http_layer(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);
        $park = IndustrialPark::factory()->create(['administrative_unit_id' => $unit->id]);

        $this->actingAs($admin)->delete(route('hr.industrial-parks.update', $park))->assertStatus(405);

        $this->assertDatabaseHas('industrial_parks', ['id' => $park->id]);
    }
}
