<?php

namespace Tests\Feature\Foundation;

use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        ApplicationNote::factory()->create(['application_id' => null]);
    }

    public function test_deleting_application_referenced_by_note_is_restricted(): void
    {
        $application = Application::factory()->create();
        ApplicationNote::factory()->create(['application_id' => $application->id]);

        $this->expectException(QueryException::class);

        $application->delete();
    }

    public function test_user_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        ApplicationNote::factory()->create(['user_id' => null]);
    }

    public function test_deleting_note_author_is_restricted(): void
    {
        $user = User::factory()->create();
        ApplicationNote::factory()->create(['user_id' => $user->id]);

        $this->expectException(QueryException::class);

        $user->delete();
    }

    public function test_soft_delete(): void
    {
        $note = ApplicationNote::factory()->create();

        $note->delete();

        $this->assertSoftDeleted('application_notes', ['id' => $note->id]);
    }

    public function test_belongs_to_application_and_user(): void
    {
        $application = Application::factory()->create();
        $user = User::factory()->create();
        $note = ApplicationNote::factory()->create([
            'application_id' => $application->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($note->application->is($application));
        $this->assertTrue($note->user->is($user));
    }
}
