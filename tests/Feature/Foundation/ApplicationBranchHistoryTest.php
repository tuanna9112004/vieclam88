<?php

namespace Tests\Feature\Foundation;

use App\Models\Application;
use App\Models\ApplicationBranchHistory;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationBranchHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        ApplicationBranchHistory::factory()->create(['application_id' => null]);
    }

    public function test_deleting_application_referenced_by_history_is_restricted(): void
    {
        $application = Application::factory()->create();
        ApplicationBranchHistory::factory()->create(['application_id' => $application->id]);

        $this->expectException(QueryException::class);

        $application->delete();
    }

    public function test_to_branch_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        ApplicationBranchHistory::factory()->create(['to_branch_id' => null]);
    }

    public function test_deleting_to_branch_referenced_by_history_is_restricted(): void
    {
        $branch = Branch::factory()->create();
        ApplicationBranchHistory::factory()->create(['to_branch_id' => $branch->id]);

        $this->expectException(QueryException::class);

        $branch->forceDelete();
    }

    public function test_from_branch_id_is_nullable_and_set_null_on_delete(): void
    {
        $branch = Branch::factory()->create();
        $history = ApplicationBranchHistory::factory()->create(['from_branch_id' => $branch->id]);

        $branch->forceDelete();

        $this->assertNull($history->fresh()->from_branch_id);
    }

    public function test_deleting_transferred_by_user_sets_transferred_by_null(): void
    {
        $admin = User::factory()->admin()->create();
        $history = ApplicationBranchHistory::factory()->create(['transferred_by' => $admin->id]);

        $admin->delete();

        $this->assertNull($history->fresh()->transferred_by);
    }

    public function test_record_has_no_updated_at_column(): void
    {
        $history = ApplicationBranchHistory::factory()->create();

        $this->assertArrayNotHasKey('updated_at', $history->getAttributes());
    }

    public function test_belongs_to_application_and_branches(): void
    {
        $application = Application::factory()->create();
        $fromBranch = Branch::factory()->create();
        $toBranch = Branch::factory()->create();
        $history = ApplicationBranchHistory::factory()->create([
            'application_id' => $application->id,
            'from_branch_id' => $fromBranch->id,
            'to_branch_id' => $toBranch->id,
        ]);

        $this->assertTrue($history->application->is($application));
        $this->assertTrue($history->fromBranch->is($fromBranch));
        $this->assertTrue($history->toBranch->is($toBranch));
    }
}
