<?php

namespace Tests\Feature\Public;

use App\Actions\Application\IssueSubmissionTokenAction;
use App\Models\AdministrativeUnit;
use App\Models\Branch;
use App\Models\CompanyLocation;
use App\Models\Job;
use App\Models\JobLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionTokenTest extends TestCase
{
    use RefreshDatabase;

    private AdministrativeUnit $unit;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = AdministrativeUnit::factory()->create();
        $this->branch = Branch::factory()->create([
            'status' => 'active',
            'administrative_unit_id' => $this->unit->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createJob(array $overrides = []): Job
    {
        $job = Job::factory()->create(array_merge([
            'status' => 'published',
            'owner_branch_id' => $this->branch->id,
        ], $overrides));

        $companyLocation = CompanyLocation::factory()->create([
            'company_id' => $job->company_id,
            'administrative_unit_id' => $this->unit->id,
        ]);

        JobLocation::factory()->create([
            'job_id' => $job->id,
            'company_location_id' => $companyLocation->id,
            'is_primary' => true,
        ]);

        return $job->fresh();
    }

    public function test_opening_an_open_job_issues_a_submission_token_in_session(): void
    {
        $job = $this->createJob();

        $this->get(route('jobs.show', $job->slug))->assertOk();

        $tokens = session(IssueSubmissionTokenAction::SESSION_KEY, []);
        $this->assertCount(1, $tokens);
        $entry = array_values($tokens)[0];
        $this->assertSame($job->id, $entry['job_id']);
    }

    public function test_opening_two_jobs_in_the_same_session_keeps_separate_tokens(): void
    {
        $jobA = $this->createJob();
        $jobB = $this->createJob();

        $this->get(route('jobs.show', $jobA->slug))->assertOk();
        $this->get(route('jobs.show', $jobB->slug))->assertOk();

        $tokens = session(IssueSubmissionTokenAction::SESSION_KEY, []);
        $this->assertCount(2, $tokens);
        $jobIds = array_column($tokens, 'job_id');
        $this->assertContains($jobA->id, $jobIds);
        $this->assertContains($jobB->id, $jobIds);
    }

    public function test_no_token_is_issued_for_a_job_not_open_for_application(): void
    {
        foreach (['paused', 'closed'] as $status) {
            $job = $this->createJob(['status' => $status]);

            $this->get(route('jobs.show', $job->slug))->assertOk();
        }

        $this->assertSame([], session(IssueSubmissionTokenAction::SESSION_KEY, []));
    }

    public function test_no_token_is_issued_for_an_expired_published_job(): void
    {
        $job = $this->createJob(['expires_at' => now()->subDay()]);

        $this->get(route('jobs.show', $job->slug))->assertOk();

        $this->assertSame([], session(IssueSubmissionTokenAction::SESSION_KEY, []));
    }

    public function test_each_token_is_unique_and_stored_with_issued_at(): void
    {
        $job = $this->createJob();

        $this->get(route('jobs.show', $job->slug))->assertOk();
        $this->get(route('jobs.show', $job->slug))->assertOk();

        $tokens = session(IssueSubmissionTokenAction::SESSION_KEY, []);
        $this->assertCount(2, $tokens);
        foreach ($tokens as $token => $entry) {
            $this->assertSame(64, strlen($token));
            $this->assertArrayHasKey('issued_at', $entry);
        }
    }
}
