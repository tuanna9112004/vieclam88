<?php

namespace Tests\Feature\Foundation;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationStatusHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        ApplicationStatusHistory::factory()->create(['application_id' => null]);
    }

    public function test_deleting_application_referenced_by_history_is_restricted(): void
    {
        $application = Application::factory()->create();
        ApplicationStatusHistory::factory()->create(['application_id' => $application->id]);

        $this->expectException(QueryException::class);

        $application->delete();
    }

    public function test_from_stage_is_nullable(): void
    {
        $history = ApplicationStatusHistory::factory()->create(['from_stage' => null]);

        $this->assertNull($history->fresh()->from_stage);
    }

    public function test_to_stage_is_required(): void
    {
        $this->expectException(QueryException::class);

        ApplicationStatusHistory::factory()->create(['to_stage' => null]);
    }

    public function test_to_stage_rejects_invalid_value(): void
    {
        $this->expectException(QueryException::class);

        ApplicationStatusHistory::factory()->create(['to_stage' => 'invalid_stage']);
    }

    public function test_deleting_changer_user_sets_changed_by_null(): void
    {
        $admin = User::factory()->admin()->create();
        $history = ApplicationStatusHistory::factory()->create(['changed_by' => $admin->id]);

        $admin->delete();

        $this->assertNull($history->fresh()->changed_by);
    }

    public function test_record_has_no_updated_at_column(): void
    {
        $history = ApplicationStatusHistory::factory()->create();

        $this->assertArrayNotHasKey('updated_at', $history->getAttributes());
    }

    public function test_belongs_to_application(): void
    {
        $application = Application::factory()->create();
        $history = ApplicationStatusHistory::factory()->create(['application_id' => $application->id]);

        $this->assertTrue($history->application->is($application));
    }
}
