<?php

namespace Tests\Feature\Hr\AdministrativeUnit;

use App\Models\AdministrativeUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

    public function test_admin_can_store_a_unit_with_provenance_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $parent = AdministrativeUnit::factory()->create();

        $response = $this->actingAs($admin)->post(route('hr.administrative-units.store'), [
            'parent_id' => $parent->id,
            'type' => 'ward',
            'name' => 'Phường Trung Tâm',
            'slug' => 'phuong-trung-tam',
            'official_code' => 'WARD-001',
            'is_active' => '1',
            'valid_from' => '2026-01-01',
            'valid_to' => null,
        ]);

        $response->assertRedirect(route('hr.administrative-units.index'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('administrative_units', [
            'parent_id' => $parent->id,
            'type' => 'ward',
            'name' => 'Phường Trung Tâm',
            'slug' => 'phuong-trung-tam',
            'official_code' => 'WARD-001',
            'is_active' => true,
            'valid_from' => '2026-01-01',
            'valid_to' => null,
        ]);
    }

    public function test_store_uses_action_upsert_identity_instead_of_creating_a_duplicate(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create([
            'official_code' => 'VN-01',
            'name' => 'Tên cũ',
            'slug' => 'ten-cu',
            'type' => 'province',
        ]);

        $response = $this->actingAs($admin)->post(route('hr.administrative-units.store'), [
            'parent_id' => null,
            'type' => 'city',
            'name' => 'Hà Nội',
            'slug' => 'ha-noi',
            'official_code' => 'VN-01',
            'is_active' => '1',
            'valid_from' => '2025-07-01',
            'valid_to' => null,
        ]);

        $response->assertRedirect(route('hr.administrative-units.index'));
        $this->assertDatabaseCount('administrative_units', 1);
        $this->assertDatabaseHas('administrative_units', [
            'id' => $unit->id,
            'official_code' => 'VN-01',
            'name' => 'Hà Nội',
            'slug' => 'ha-noi',
            'type' => 'city',
        ]);
    }

    public function test_store_validates_parent_type_slug_code_and_provenance(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('hr.administrative-units.store'), [
            'parent_id' => 999999,
            'type' => 'district',
            'name' => '',
            'slug' => 'Slug Không Hợp Lệ',
            'official_code' => str_repeat('X', 21),
            'is_active' => '0',
            'valid_from' => '2026-07-02',
            'valid_to' => '2026-07-01',
        ]);

        $response->assertSessionHasErrors([
            'parent_id',
            'type',
            'name',
            'slug',
            'official_code',
            'valid_to',
        ]);
        $this->assertDatabaseCount('administrative_units', 0);
    }

    public function test_inactive_unit_requires_an_end_of_validity_date(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('hr.administrative-units.store'), [
            'parent_id' => null,
            'type' => 'province',
            'name' => 'Tỉnh cũ',
            'slug' => 'tinh-cu',
            'official_code' => 'OLD-01',
            'is_active' => '0',
            'valid_from' => null,
            'valid_to' => null,
        ])->assertSessionHasErrors('valid_to');

        $this->assertDatabaseMissing('administrative_units', ['official_code' => 'OLD-01']);
    }

    public function test_update_targets_the_route_model_when_identity_fields_change(): void
    {
        $admin = User::factory()->admin()->create();
        $newParent = AdministrativeUnit::factory()->create(['official_code' => 'PARENT']);
        $unit = AdministrativeUnit::factory()->create([
            'official_code' => 'OLD-CODE',
            'name' => 'Tên cũ',
            'slug' => 'ten-cu',
        ]);

        $response = $this->actingAs($admin)->put(
            route('hr.administrative-units.update', $unit),
            [
                'parent_id' => $newParent->id,
                'type' => 'ward',
                'name' => 'Tên mới',
                'slug' => 'ten-moi',
                'official_code' => 'NEW-CODE',
                'is_active' => '1',
                'valid_from' => '2026-01-01',
                'valid_to' => null,
            ]
        );

        $response->assertRedirect(route('hr.administrative-units.index'));
        $this->assertDatabaseCount('administrative_units', 2);
        $this->assertDatabaseHas('administrative_units', [
            'id' => $unit->id,
            'parent_id' => $newParent->id,
            'official_code' => 'NEW-CODE',
            'name' => 'Tên mới',
            'slug' => 'ten-moi',
            'type' => 'ward',
        ]);
        $this->assertDatabaseMissing('administrative_units', ['official_code' => 'OLD-CODE']);
    }

    public function test_update_rejects_an_official_code_owned_by_another_unit(): void
    {
        $admin = User::factory()->admin()->create();
        $other = AdministrativeUnit::factory()->create(['official_code' => 'TAKEN']);
        $unit = AdministrativeUnit::factory()->create(['official_code' => 'CURRENT']);

        $response = $this->actingAs($admin)->put(
            route('hr.administrative-units.update', $unit),
            [
                'parent_id' => null,
                'type' => $unit->type,
                'name' => $unit->name,
                'slug' => $unit->slug,
                'official_code' => $other->official_code,
                'is_active' => '1',
                'valid_from' => null,
                'valid_to' => null,
            ]
        );

        $response->assertSessionHasErrors('official_code');
        $this->assertSame('CURRENT', $unit->fresh()->official_code);
    }

    public function test_edit_parent_selector_excludes_self_and_all_descendants(): void
    {
        $admin = User::factory()->admin()->create();
        $root = AdministrativeUnit::factory()->create(['official_code' => 'ROOT']);
        $child = AdministrativeUnit::factory()->create([
            'official_code' => 'CHILD',
            'parent_id' => $root->id,
        ]);
        $grandchild = AdministrativeUnit::factory()->create([
            'official_code' => 'GRANDCHILD',
            'parent_id' => $child->id,
        ]);
        $unrelated = AdministrativeUnit::factory()->create(['official_code' => 'UNRELATED']);

        $response = $this->actingAs($admin)->get(route('hr.administrative-units.index', [
            'edit' => $root->id,
        ]));

        $response->assertOk()
            ->assertDontSee('data-edit-parent-option="'.$root->id.'"', false)
            ->assertDontSee('data-edit-parent-option="'.$child->id.'"', false)
            ->assertDontSee('data-edit-parent-option="'.$grandchild->id.'"', false)
            ->assertSee('data-edit-parent-option="'.$unrelated->id.'"', false);
    }

    public function test_action_rechecks_and_rejects_descendant_parent_from_a_direct_request(): void
    {
        $admin = User::factory()->admin()->create();
        $root = AdministrativeUnit::factory()->create([
            'official_code' => 'ROOT',
            'slug' => 'root',
        ]);
        $child = AdministrativeUnit::factory()->create([
            'official_code' => 'CHILD',
            'parent_id' => $root->id,
        ]);
        $grandchild = AdministrativeUnit::factory()->create([
            'official_code' => 'GRANDCHILD',
            'parent_id' => $child->id,
        ]);

        $response = $this->actingAs($admin)->from(route('hr.administrative-units.index', [
            'edit' => $root->id,
        ]))->put(route('hr.administrative-units.update', $root), [
            'parent_id' => $grandchild->id,
            'type' => $root->type,
            'name' => $root->name,
            'slug' => $root->slug,
            'official_code' => $root->official_code,
            'is_active' => '1',
            'valid_from' => null,
            'valid_to' => null,
        ]);

        $response->assertRedirect(route('hr.administrative-units.index', ['edit' => $root->id]));
        $response->assertSessionHasErrors('parent_id');
        $this->assertNull($root->fresh()->parent_id);
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
