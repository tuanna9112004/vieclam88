<?php

namespace Tests\Feature\Foundation;

use App\Models\Application;
use App\Models\ApplicationAppointment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationAppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        ApplicationAppointment::factory()->create(['application_id' => null]);
    }

    public function test_deleting_application_referenced_by_appointment_is_restricted(): void
    {
        $application = Application::factory()->create();
        ApplicationAppointment::factory()->create(['application_id' => $application->id]);

        $this->expectException(QueryException::class);

        $application->delete();
    }

    public function test_type_rejects_invalid_value(): void
    {
        $this->expectException(QueryException::class);

        ApplicationAppointment::factory()->create(['type' => 'invalid_type']);
    }

    public function test_status_defaults_to_scheduled(): void
    {
        $appointment = ApplicationAppointment::factory()->create();

        $this->assertSame('scheduled', $appointment->status);
    }

    public function test_rescheduling_does_not_overwrite_existing_record_but_updates_status(): void
    {
        $application = Application::factory()->create();
        $original = ApplicationAppointment::factory()->create([
            'application_id' => $application->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $original->update(['status' => 'cancelled']);
        ApplicationAppointment::factory()->create([
            'application_id' => $application->id,
            'scheduled_at' => now()->addDays(2),
        ]);

        $this->assertSame('cancelled', $original->fresh()->status);
        $this->assertSame(2, ApplicationAppointment::where('application_id', $application->id)->count());
    }

    public function test_deleting_created_by_user_is_restricted(): void
    {
        $staff = User::factory()->create();
        ApplicationAppointment::factory()->create(['created_by' => $staff->id]);

        $this->expectException(QueryException::class);

        $staff->delete();
    }

    public function test_deleting_completed_by_user_sets_completed_by_null(): void
    {
        $staff = User::factory()->create();
        $appointment = ApplicationAppointment::factory()->create(['completed_by' => $staff->id]);

        $staff->delete();

        $this->assertNull($appointment->fresh()->completed_by);
    }

    public function test_belongs_to_application(): void
    {
        $application = Application::factory()->create();
        $appointment = ApplicationAppointment::factory()->create(['application_id' => $application->id]);

        $this->assertTrue($appointment->application->is($application));
    }
}
