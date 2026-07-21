<?php

namespace Tests\Feature\Foundation;

use App\Models\Job;
use App\Models\JobVerification;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        JobVerification::factory()->create(['job_id' => null]);
    }

    public function test_deleting_job_referenced_by_verification_is_restricted(): void
    {
        $job = Job::factory()->create();
        JobVerification::factory()->create(['job_id' => $job->id]);

        $this->expectException(QueryException::class);

        $job->forceDelete();
    }

    public function test_verified_by_is_required(): void
    {
        $this->expectException(QueryException::class);

        JobVerification::factory()->create(['verified_by' => null]);
    }

    public function test_deleting_verifier_user_is_restricted(): void
    {
        $admin = User::factory()->admin()->create();
        JobVerification::factory()->create(['verified_by' => $admin->id]);

        $this->expectException(QueryException::class);

        $admin->delete();
    }

    public function test_result_rejects_invalid_value(): void
    {
        $this->expectException(QueryException::class);

        JobVerification::factory()->create(['result' => 'invalid_result']);
    }

    public function test_record_has_no_updated_at_column(): void
    {
        $verification = JobVerification::factory()->create();

        $this->assertArrayNotHasKey('updated_at', $verification->getAttributes());
    }

    public function test_belongs_to_job_and_verifier(): void
    {
        $job = Job::factory()->create();
        $admin = User::factory()->admin()->create();
        $verification = JobVerification::factory()->create(['job_id' => $job->id, 'verified_by' => $admin->id]);

        $this->assertTrue($verification->job->is($job));
        $this->assertTrue($verification->verifiedBy->is($admin));
    }
}
