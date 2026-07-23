<?php

namespace Tests\Feature\Hr\AdministrativeUnit;

use App\Models\AdministrativeUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TASK 1.3: UI quản trị đơn vị hành chính cũ chuyển read-only/deprecated — store/update bị chặn
 * qua AdministrativeUnitPolicy (kể cả Admin). Logic upsert/cycle-guard vẫn được test đầy đủ ở
 * cấp Action, xem tests/Feature/Foundation/AdministrativeUnitUpsertTest.php.
 */
class AdministrativeUnitManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_search_and_paginate_the_hierarchy(): void
    {
        $admin = User::factory()->admin()->create();
        $parent = AdministrativeUnit::factory()->create([
            'name' => 'Tỉnh Bắc Ninh',
            'slug' => 'bac-ninh',
        ]);
        AdministrativeUnit::factory()->create([
            'parent_id' => $parent->id,
            'name' => 'Phường Kinh Bắc',
            'slug' => 'kinh-bac',
            'type' => 'ward',
        ]);
        foreach (range(1, 20) as $index) {
            AdministrativeUnit::query()->create([
                'parent_id' => null,
                'official_code' => 'PAGE-'.$index,
                'name' => 'Đơn vị '.$index,
                'slug' => 'don-vi-'.$index,
                'type' => 'province',
                'is_active' => true,
            ]);
        }

        $searchResponse = $this->actingAs($admin)->get(route('hr.administrative-units.index', [
            'q' => 'Kinh Bắc',
        ]));

        $searchResponse->assertOk()
            ->assertSee('Phường Kinh Bắc')
            ->assertSee('Tỉnh Bắc Ninh')
            ->assertViewHas(
                'administrativeUnits',
                fn ($units): bool => $units->total() === 1 && $units->perPage() === 20
            );

        $this->actingAs($admin)
            ->get(route('hr.administrative-units.index'))
            ->assertOk()
            ->assertViewHas(
                'administrativeUnits',
                fn ($units): bool => $units->total() === 22 && $units->count() === 20
            );
    }

    public function test_index_shows_deprecation_notice_and_no_edit_action(): void
    {
        $admin = User::factory()->admin()->create();
        AdministrativeUnit::factory()->create(['name' => 'Đơn vị mẫu']);

        $this->actingAs($admin)->get(route('hr.administrative-units.index'))
            ->assertOk()
            ->assertSee('read-only')
            ->assertDontSee('Lưu đơn vị')
            ->assertDontSee('Lưu thay đổi');
    }

    public function test_guest_is_redirected_and_staff_receives_403_for_direct_urls(): void
    {
        $staff = User::factory()->create();
        $unit = AdministrativeUnit::factory()->create([
            'official_code' => 'STAFF-BLOCKED',
            'name' => 'Tên ban đầu',
        ]);
        $payload = [
            'parent_id' => null,
            'type' => 'province',
            'name' => 'Không được lưu',
            'slug' => 'khong-duoc-luu',
            'official_code' => 'STAFF-NEW',
            'is_active' => '1',
            'valid_from' => null,
            'valid_to' => null,
        ];

        $this->get(route('hr.administrative-units.index'))
            ->assertRedirect(route('hr.login'));

        $this->actingAs($staff)
            ->get(route('hr.administrative-units.index'))
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(route('hr.administrative-units.store'), $payload)
            ->assertForbidden();

        $this->actingAs($staff)
            ->put(route('hr.administrative-units.update', $unit), $payload)
            ->assertForbidden();

        $this->assertDatabaseMissing('administrative_units', ['official_code' => 'STAFF-NEW']);
        $this->assertSame('Tên ban đầu', $unit->fresh()->name);
    }

    public function test_admin_also_receives_403_for_store_and_update_now_that_ui_is_deprecated(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create([
            'official_code' => 'ADMIN-BLOCKED',
            'name' => 'Tên ban đầu',
        ]);
        $payload = [
            'parent_id' => null,
            'type' => 'province',
            'name' => 'Không được lưu',
            'slug' => 'khong-duoc-luu',
            'official_code' => 'ADMIN-NEW',
            'is_active' => '1',
            'valid_from' => null,
            'valid_to' => null,
        ];

        $this->actingAs($admin)
            ->post(route('hr.administrative-units.store'), $payload)
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('hr.administrative-units.update', $unit), $payload)
            ->assertForbidden();

        $this->assertDatabaseMissing('administrative_units', ['official_code' => 'ADMIN-NEW']);
        $this->assertSame('Tên ban đầu', $unit->fresh()->name);
    }

    public function test_administrative_unit_cannot_be_hard_deleted_via_http(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create();

        $this->actingAs($admin)
            ->delete(route('hr.administrative-units.update', $unit))
            ->assertStatus(405);

        $this->assertDatabaseHas('administrative_units', ['id' => $unit->id]);
    }
}
