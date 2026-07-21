<?php

namespace Tests\Feature\Foundation;

use App\Models\Branch;
use App\Models\Job;
use App\Models\JobBranchHistory;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobBranchHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        JobBranchHistory::factory()->create(['job_id' => null]);
    }

    public function test_deleting_job_referenced_by_history_is_restricted(): void
    {
        $job = Job::factory()->create();
        JobBranchHistory::factory()->create(['job_id' => $job->id]);

        $this->expectException(QueryException::class);

        $job->forceDelete();
    }

    public function test_from_branch_id_is_nullable(): void
    {
        $history = JobBranchHistory::factory()->create(['from_branch_id' => null]);

        $this->assertNull($history->fresh()->from_branch_id);
    }

    public function test_deleting_from_branch_sets_from_branch_id_null(): void
    {
        $fromBranch = Branch::factory()->create();
        $history = JobBranchHistory::factory()->create(['from_branch_id' => $fromBranch->id]);

        $fromBranch->forceDelete();

        $this->assertNull($history->fresh()->from_branch_id);
    }

    public function test_to_branch_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        JobBranchHistory::factory()->create(['to_branch_id' => null]);
    }

    public function test_deleting_to_branch_referenced_by_history_is_restricted(): void
    {
        $toBranch = Branch::factory()->create();
        JobBranchHistory::factory()->create(['to_branch_id' => $toBranch->id]);

        $this->expectException(QueryException::class);

        $toBranch->forceDelete();
    }

    public function test_changed_by_is_required(): void
    {
        $this->expectException(QueryException::class);

        JobBranchHistory::factory()->create(['changed_by' => null]);
    }

    public function test_deleting_changer_user_is_restricted(): void
    {
        $admin = User::factory()->admin()->create();
        JobBranchHistory::factory()->create(['changed_by' => $admin->id]);

        $this->expectException(QueryException::class);

        $admin->delete();
    }

    public function test_record_has_no_updated_at_column(): void
    {
        $history = JobBranchHistory::factory()->create();

        $this->assertArrayNotHasKey('updated_at', $history->getAttributes());
    }

    public function test_belongs_to_job_from_branch_to_branch_and_changer(): void
    {
        $job = Job::factory()->create();
        $fromBranch = Branch::factory()->create();
        $toBranch = Branch::factory()->create();
        $admin = User::factory()->admin()->create();
        $history = JobBranchHistory::factory()->create([
            'job_id' => $job->id,
            'from_branch_id' => $fromBranch->id,
            'to_branch_id' => $toBranch->id,
            'changed_by' => $admin->id,
        ]);

        $this->assertTrue($history->job->is($job));
        $this->assertTrue($history->fromBranch->is($fromBranch));
        $this->assertTrue($history->toBranch->is($toBranch));
        $this->assertTrue($history->changedBy->is($admin));
    }
}
