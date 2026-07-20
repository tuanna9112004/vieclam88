<?php

namespace Tests\Feature\Foundation;

use App\Actions\AdministrativeUnit\UpsertAdministrativeUnitAction;
use App\Models\AdministrativeUnit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdministrativeUnitUpsertTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_new_unit_when_official_code_not_found(): void
    {
        $unit = (new UpsertAdministrativeUnitAction)->handle([
            'official_code' => 'VN-01',
            'parent_id' => null,
            'name' => 'Hà Nội',
            'slug' => 'ha-noi',
            'type' => 'city',
        ]);

        $this->assertDatabaseHas('administrative_units', [
            'id' => $unit->id,
            'official_code' => 'VN-01',
            'slug' => 'ha-noi',
        ]);
    }

    public function test_updates_existing_unit_matched_by_official_code(): void
    {
        $existing = AdministrativeUnit::factory()->create([
            'official_code' => 'VN-01',
            'name' => 'Ha Noi (ten cu)',
            'slug' => 'ha-noi-cu',
        ]);

        $updated = (new UpsertAdministrativeUnitAction)->handle([
            'official_code' => 'VN-01',
            'parent_id' => null,
            'name' => 'Hà Nội',
            'slug' => 'ha-noi-cu',
            'type' => 'city',
        ]);

        $this->assertSame($existing->id, $updated->id);
        $this->assertSame('Hà Nội', $updated->fresh()->name);
        $this->assertDatabaseCount('administrative_units', 1);
    }

    public function test_falls_back_to_parent_and_slug_when_no_official_code(): void
    {
        $parent = AdministrativeUnit::factory()->create();
        $existing = AdministrativeUnit::factory()->create([
            'parent_id' => $parent->id,
            'official_code' => null,
            'slug' => 'phuong-1',
            'name' => 'Phuong 1 (ten cu)',
        ]);

        $updated = (new UpsertAdministrativeUnitAction)->handle([
            'official_code' => null,
            'parent_id' => $parent->id,
            'name' => 'Phường 1',
            'slug' => 'phuong-1',
            'type' => 'ward',
        ]);

        $this->assertSame($existing->id, $updated->id);
        $this->assertSame('Phường 1', $updated->fresh()->name);
        $this->assertDatabaseCount('administrative_units', 2);
    }

    public function test_different_official_codes_sharing_same_slug_do_not_collapse_into_one_row(): void
    {
        $first = (new UpsertAdministrativeUnitAction)->handle([
            'official_code' => 'A',
            'parent_id' => null,
            'name' => 'Unit A',
            'slug' => 'x',
            'type' => 'province',
        ]);

        try {
            (new UpsertAdministrativeUnitAction)->handle([
                'official_code' => 'B',
                'parent_id' => null,
                'name' => 'Unit B',
                'slug' => 'x',
                'type' => 'province',
            ]);

            $this->fail('Expected QueryException from root_slug_key unique constraint.');
        } catch (QueryException) {
            $this->assertDatabaseCount('administrative_units', 1);
            $this->assertSame('A', $first->fresh()->official_code);
        }
    }

    public function test_rejects_unit_becoming_its_own_parent(): void
    {
        $unit = AdministrativeUnit::factory()->create(['official_code' => 'VN-01']);

        $this->expectException(ValidationException::class);

        (new UpsertAdministrativeUnitAction)->handle([
            'official_code' => 'VN-01',
            'parent_id' => $unit->id,
            'name' => $unit->name,
            'slug' => $unit->slug,
            'type' => $unit->type,
        ]);
    }

    public function test_rejects_direct_cycle_between_parent_and_child(): void
    {
        $a = AdministrativeUnit::factory()->create(['official_code' => 'A']);
        $b = AdministrativeUnit::factory()->create(['official_code' => 'B', 'parent_id' => $a->id]);

        $this->expectException(ValidationException::class);

        (new UpsertAdministrativeUnitAction)->handle([
            'official_code' => 'A',
            'parent_id' => $b->id,
            'name' => $a->name,
            'slug' => $a->slug,
            'type' => $a->type,
        ]);
    }

    public function test_rejects_deep_cycle_through_grandchild(): void
    {
        $a = AdministrativeUnit::factory()->create(['official_code' => 'A']);
        $b = AdministrativeUnit::factory()->create(['official_code' => 'B', 'parent_id' => $a->id]);
        $c = AdministrativeUnit::factory()->create(['official_code' => 'C', 'parent_id' => $b->id]);

        try {
            (new UpsertAdministrativeUnitAction)->handle([
                'official_code' => 'A',
                'parent_id' => $c->id,
                'name' => $a->name,
                'slug' => $a->slug,
                'type' => $a->type,
            ]);

            $this->fail('Expected ValidationException for cycle through grandchild.');
        } catch (ValidationException) {
            $this->assertNull($a->fresh()->parent_id);
        }
    }

    public function test_reparenting_onto_a_preexisting_unrelated_cycle_does_not_hang(): void
    {
        $x = AdministrativeUnit::factory()->create(['official_code' => 'X']);
        $y = AdministrativeUnit::factory()->create(['official_code' => 'Y', 'parent_id' => $x->id]);
        // Dữ liệu hỏng có sẵn (không tạo qua Action): X <-> Y tạo thành cycle không liên quan tới $w.
        $x->forceFill(['parent_id' => $y->id])->saveQuietly();

        $w = AdministrativeUnit::factory()->create(['official_code' => 'W']);

        $updated = (new UpsertAdministrativeUnitAction)->handle([
            'official_code' => 'W',
            'parent_id' => $x->id,
            'name' => $w->name,
            'slug' => $w->slug,
            'type' => $w->type,
        ]);

        $this->assertSame($x->id, $updated->fresh()->parent_id);
    }

    public function test_valid_reparenting_to_unrelated_unit_succeeds(): void
    {
        $oldParent = AdministrativeUnit::factory()->create(['official_code' => 'OLD']);
        $newParent = AdministrativeUnit::factory()->create(['official_code' => 'NEW']);
        $child = AdministrativeUnit::factory()->create([
            'official_code' => 'CHILD',
            'parent_id' => $oldParent->id,
        ]);

        $updated = (new UpsertAdministrativeUnitAction)->handle([
            'official_code' => 'CHILD',
            'parent_id' => $newParent->id,
            'name' => $child->name,
            'slug' => $child->slug,
            'type' => $child->type,
        ]);

        $this->assertSame($newParent->id, $updated->fresh()->parent_id);
    }
}
