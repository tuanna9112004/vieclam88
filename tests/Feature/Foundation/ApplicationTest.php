<?php

namespace Tests\Feature\Foundation;

use App\Models\Application;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\RecruitmentSource;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_and_job_pair_must_be_unique(): void
    {
        $candidate = Candidate::factory()->create();
        $job = Job::factory()->create();
        Application::factory()->create(['candidate_id' => $candidate->id, 'job_id' => $job->id]);

        $this->expectException(QueryException::class);

        Application::factory()->create(['candidate_id' => $candidate->id, 'job_id' => $job->id]);
    }

    public function test_submission_token_must_be_unique(): void
    {
        $application = Application::factory()->create();

        $this->expectException(QueryException::class);

        Application::factory()->create(['submission_token' => $application->submission_token]);
    }

    public function test_stage_defaults_to_new_and_workflow_cycle_defaults_to_1(): void
    {
        $application = Application::factory()->create();

        $this->assertSame('new', $application->stage);
        $this->assertSame(1, $application->workflow_cycle);
    }

    public function test_deleting_candidate_referenced_by_application_is_restricted(): void
    {
        $candidate = Candidate::factory()->create();
        Application::factory()->create(['candidate_id' => $candidate->id]);

        $this->expectException(QueryException::class);

        $candidate->forceDelete();
    }

    public function test_deleting_job_referenced_by_application_is_restricted(): void
    {
        $job = Job::factory()->create();
        Application::factory()->create(['job_id' => $job->id]);

        $this->expectException(QueryException::class);

        $job->forceDelete();
    }

    public function test_deleting_owner_branch_referenced_by_application_is_restricted(): void
    {
        $branch = Branch::factory()->create();
        Application::factory()->create(['owner_branch_id' => $branch->id]);

        $this->expectException(QueryException::class);

        $branch->forceDelete();
    }

    public function test_deleting_recruitment_source_sets_source_id_null(): void
    {
        $source = RecruitmentSource::factory()->create();
        $application = Application::factory()->create(['source_id' => $source->id]);

        $source->delete();

        $this->assertNull($application->fresh()->source_id);
    }

    public function test_submission_snapshot_and_job_snapshot_cast_to_array(): void
    {
        $application = Application::factory()->create([
            'submission_snapshot' => ['full_name' => 'Nguyen Van A'],
            'job_snapshot' => ['title' => 'Cong nhan'],
        ]);

        $fresh = $application->fresh();
        $this->assertIsArray($fresh->submission_snapshot);
        $this->assertSame('Nguyen Van A', $fresh->submission_snapshot['full_name']);
        $this->assertIsArray($fresh->job_snapshot);
    }

    public function test_belongs_to_candidate_and_job(): void
    {
        $candidate = Candidate::factory()->create();
        $job = Job::factory()->create();
        $application = Application::factory()->create(['candidate_id' => $candidate->id, 'job_id' => $job->id]);

        $this->assertTrue($application->candidate->is($candidate));
        $this->assertTrue($application->job->is($job));
    }
}
