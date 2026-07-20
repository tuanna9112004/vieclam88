<?php

namespace Tests\Feature\Foundation;

use App\Models\AdministrativeUnit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministrativeUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_root_units_cannot_share_the_same_slug(): void
    {
        AdministrativeUnit::factory()->create(['parent_id' => null, 'slug' => 'ha-noi']);

        $this->expectException(QueryException::class);

        AdministrativeUnit::factory()->create(['parent_id' => null, 'slug' => 'ha-noi']);
    }

    public function test_child_units_under_different_parents_can_share_the_same_slug(): void
    {
        $parentA = AdministrativeUnit::factory()->create();
        $parentB = AdministrativeUnit::factory()->create();

        $childA = AdministrativeUnit::factory()->create(['parent_id' => $parentA->id, 'slug' => 'phuong-1']);
        $childB = AdministrativeUnit::factory()->create(['parent_id' => $parentB->id, 'slug' => 'phuong-1']);

        $this->assertNotEquals($childA->id, $childB->id);
        $this->assertDatabaseHas('administrative_units', ['id' => $childA->id, 'slug' => 'phuong-1']);
        $this->assertDatabaseHas('administrative_units', ['id' => $childB->id, 'slug' => 'phuong-1']);
    }

    public function test_same_parent_cannot_have_two_children_with_the_same_slug(): void
    {
        $parent = AdministrativeUnit::factory()->create();
        AdministrativeUnit::factory()->create(['parent_id' => $parent->id, 'slug' => 'phuong-1']);

        $this->expectException(QueryException::class);

        AdministrativeUnit::factory()->create(['parent_id' => $parent->id, 'slug' => 'phuong-1']);
    }

    public function test_parent_child_relationship(): void
    {
        $parent = AdministrativeUnit::factory()->create();
        $child = AdministrativeUnit::factory()->create(['parent_id' => $parent->id]);

        $this->assertTrue($parent->children->contains($child));
        $this->assertTrue($child->parent->is($parent));
    }
}
