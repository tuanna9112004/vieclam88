<?php

namespace Tests\Feature\Foundation;

use App\Models\Application;
use App\Models\ApplicationContactAttempt;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationContactAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        ApplicationContactAttempt::factory()->create(['application_id' => null]);
    }

    public function test_deleting_application_referenced_by_attempt_is_restricted(): void
    {
        $application = Application::factory()->create();
        ApplicationContactAttempt::factory()->create(['application_id' => $application->id]);

        $this->expectException(QueryException::class);

        $application->delete();
    }

    public function test_contacted_by_is_required(): void
    {
        $this->expectException(QueryException::class);

        ApplicationContactAttempt::factory()->create(['contacted_by' => null]);
    }

    public function test_deleting_contacted_by_user_is_restricted(): void
    {
        $staff = User::factory()->create();
        ApplicationContactAttempt::factory()->create(['contacted_by' => $staff->id]);

        $this->expectException(QueryException::class);

        $staff->delete();
    }

    public function test_result_rejects_invalid_value(): void
    {
        $this->expectException(QueryException::class);

        ApplicationContactAttempt::factory()->create(['result' => 'invalid_result']);
    }

    public function test_record_has_no_updated_at_column(): void
    {
        $attempt = ApplicationContactAttempt::factory()->create();

        $this->assertArrayNotHasKey('updated_at', $attempt->getAttributes());
    }

    public function test_belongs_to_application_and_contacted_by(): void
    {
        $application = Application::factory()->create();
        $staff = User::factory()->create();
        $attempt = ApplicationContactAttempt::factory()->create([
            'application_id' => $application->id,
            'contacted_by' => $staff->id,
        ]);

        $this->assertTrue($attempt->application->is($application));
        $this->assertTrue($attempt->contactedBy->is($staff));
    }
}
