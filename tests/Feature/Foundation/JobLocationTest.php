<?php

namespace Tests\Feature\Foundation;

use App\Models\Job;
use App\Models\JobLocation;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_and_company_location_pair_must_be_unique(): void
    {
        $job = Job::factory()->create();
        $location = \App\Models\CompanyLocation::factory()->create();
        JobLocation::factory()->create(['job_id' => $job->id, 'company_location_id' => $location->id]);

        $this->expectException(QueryException::class);

        JobLocation::factory()->create(['job_id' => $job->id, 'company_location_id' => $location->id]);
    }

    public function test_only_one_primary_location_allowed_per_job(): void
    {
        $job = Job::factory()->create();
        JobLocation::factory()->create(['job_id' => $job->id, 'is_primary' => true]);

        $this->expectException(QueryException::class);

        JobLocation::factory()->create(['job_id' => $job->id, 'is_primary' => true]);
    }

    public function test_multiple_non_primary_locations_allowed_per_job(): void
    {
        $job = Job::factory()->create();
        JobLocation::factory()->create(['job_id' => $job->id, 'is_primary' => false]);
        JobLocation::factory()->create(['job_id' => $job->id, 'is_primary' => false]);

        $this->assertSame(2, JobLocation::where('job_id', $job->id)->count());
    }

    public function test_deleting_job_cascades_job_locations(): void
    {
        $job = Job::factory()->create();
        $jobLocation = JobLocation::factory()->create(['job_id' => $job->id]);

        $job->forceDelete();

        $this->assertDatabaseMissing('job_locations', ['id' => $jobLocation->id]);
    }

    public function test_belongs_to_job_and_company_location(): void
    {
        $job = Job::factory()->create();
        $location = \App\Models\CompanyLocation::factory()->create();
        $jobLocation = JobLocation::factory()->create(['job_id' => $job->id, 'company_location_id' => $location->id]);

        $this->assertTrue($jobLocation->job->is($job));
        $this->assertTrue($jobLocation->companyLocation->is($location));
    }
}
