<?php

namespace Tests\Feature\Foundation;

use App\Models\AdministrativeUnit;
use App\Models\Branch;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    public function test_code_must_be_unique(): void
    {
        Branch::factory()->create(['code' => 'HN-01']);

        $this->expectException(QueryException::class);

        Branch::factory()->create(['code' => 'HN-01']);
    }

    public function test_administrative_unit_is_required(): void
    {
        $this->expectException(QueryException::class);

        Branch::factory()->create(['administrative_unit_id' => null]);
    }

    public function test_deleting_administrative_unit_referenced_by_branch_is_restricted(): void
    {
        $unit = AdministrativeUnit::factory()->create();
        Branch::factory()->create(['administrative_unit_id' => $unit->id]);

        $this->expectException(QueryException::class);

        $unit->delete();
    }

    public function test_soft_delete_keeps_branch_row(): void
    {
        $branch = Branch::factory()->create();

        $branch->delete();

        $this->assertSoftDeleted('branches', ['id' => $branch->id]);
        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
    }

    public function test_restoring_a_soft_deleted_branch(): void
    {
        $branch = Branch::factory()->create();
        $branch->delete();

        $branch->restore();

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'deleted_at' => null]);
    }

    public function test_belongs_to_administrative_unit(): void
    {
        $unit = AdministrativeUnit::factory()->create();
        $branch = Branch::factory()->create(['administrative_unit_id' => $unit->id]);

        $this->assertTrue($branch->administrativeUnit->is($unit));
    }
}
