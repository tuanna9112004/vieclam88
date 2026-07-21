<?php

namespace Tests\Feature\Foundation;

use App\Models\Job;
use App\Models\JobStatusHistory;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobStatusHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        JobStatusHistory::factory()->create(['job_id' => null]);
    }

    public function test_deleting_job_referenced_by_history_is_restricted(): void
    {
        $job = Job::factory()->create();
        JobStatusHistory::factory()->create(['job_id' => $job->id]);

        $this->expectException(QueryException::class);

        $job->forceDelete();
    }

    public function test_from_status_is_nullable(): void
    {
        $history = JobStatusHistory::factory()->create(['from_status' => null]);

        $this->assertNull($history->fresh()->from_status);
    }

    public function test_to_status_is_required(): void
    {
        $this->expectException(QueryException::class);

        JobStatusHistory::factory()->create(['to_status' => null]);
    }

    public function test_to_status_rejects_invalid_value(): void
    {
        $this->expectException(QueryException::class);

        JobStatusHistory::factory()->create(['to_status' => 'invalid_status']);
    }

    public function test_changed_by_is_required(): void
    {
        $this->expectException(QueryException::class);

        JobStatusHistory::factory()->create(['changed_by' => null]);
    }

    public function test_deleting_changer_user_is_restricted(): void
    {
        $admin = User::factory()->admin()->create();
        JobStatusHistory::factory()->create(['changed_by' => $admin->id]);

        $this->expectException(QueryException::class);

        $admin->delete();
    }

    public function test_record_has_no_updated_at_column(): void
    {
        $history = JobStatusHistory::factory()->create();

        $this->assertArrayNotHasKey('updated_at', $history->getAttributes());
    }

    public function test_belongs_to_job_and_changer(): void
    {
        $job = Job::factory()->create();
        $admin = User::factory()->admin()->create();
        $history = JobStatusHistory::factory()->create(['job_id' => $job->id, 'changed_by' => $admin->id]);

        $this->assertTrue($history->job->is($job));
        $this->assertTrue($history->changedBy->is($admin));
    }
}
