<?php

namespace Tests\Feature\Hr\Application;

use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\Branch;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApplicationNoteTest extends TestCase
{
    use RefreshDatabase;

    private function applicationForBranch(int $branchId, array $overrides = []): Application
    {
        $job = Job::factory()->create(['owner_branch_id' => $branchId]);

        return Application::factory()->create(array_merge([
            'job_id' => $job->id,
            'owner_branch_id' => $branchId,
        ], $overrides));
    }

    public function test_guest_is_redirected_from_store(): void
    {
        $application = $this->applicationForBranch(Branch::factory()->create()->id);

        $this->post(route('hr.applications.notes.store', $application), ['content' => 'ghi chu'])
            ->assertRedirect(route('hr.login'));
    }

    public function test_staff_of_own_branch_can_create_note(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);

        $response = $this->actingAs($staff)->post(
            route('hr.applications.notes.store', $application),
            ['content' => 'Ứng viên hẹn gọi lại chiều mai.']
        );

        $response->assertRedirect(route('hr.applications.show', $application));
        $this->assertDatabaseHas('application_notes', [
            'application_id' => $application->id,
            'user_id' => $staff->id,
            'content' => 'Ứng viên hẹn gọi lại chiều mai.',
        ]);
    }

    public function test_staff_of_other_branch_cannot_create_note(): void
    {
        $staff = User::factory()->create();
        $otherBranch = Branch::factory()->create();
        $application = $this->applicationForBranch($otherBranch->id);

        $this->actingAs($staff)
            ->post(route('hr.applications.notes.store', $application), ['content' => 'ghi chu'])
            ->assertForbidden();

        $this->assertSame(0, ApplicationNote::count());
    }

    public function test_admin_can_create_note_for_any_branch(): void
    {
        $admin = User::factory()->admin()->create();
        $application = $this->applicationForBranch(Branch::factory()->create()->id);

        $this->actingAs($admin)
            ->post(route('hr.applications.notes.store', $application), ['content' => 'ghi chu admin'])
            ->assertRedirect(route('hr.applications.show', $application));

        $this->assertSame(1, ApplicationNote::count());
    }

    public function test_content_is_required(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);

        $response = $this->actingAs($staff)->post(
            route('hr.applications.notes.store', $application),
            ['content' => '']
        );

        $response->assertSessionHasErrors('content');
        $this->assertSame(0, ApplicationNote::count());
    }

    public function test_creator_can_update_own_note(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);
        $note = ApplicationNote::factory()->create([
            'application_id' => $application->id,
            'user_id' => $staff->id,
            'content' => 'Nội dung cũ',
        ]);

        $response = $this->actingAs($staff)->put(
            route('hr.applications.notes.update', [$application, $note]),
            ['content' => 'Nội dung mới']
        );

        $response->assertRedirect(route('hr.applications.show', $application));
        $note->refresh();
        $this->assertSame('Nội dung mới', $note->content);
        $this->assertNotNull($note->edited_at);
    }

    public function test_another_staff_of_same_branch_cannot_update_someone_elses_note(): void
    {
        $creator = User::factory()->create();
        $otherStaff = User::factory()->create(['branch_id' => $creator->branch_id]);
        $application = $this->applicationForBranch($creator->branch_id);
        $note = ApplicationNote::factory()->create([
            'application_id' => $application->id,
            'user_id' => $creator->id,
            'content' => 'Nội dung cũ',
        ]);

        $this->actingAs($otherStaff)
            ->put(route('hr.applications.notes.update', [$application, $note]), ['content' => 'Sửa trộm'])
            ->assertForbidden();

        $this->assertSame('Nội dung cũ', $note->fresh()->content);
    }

    public function test_admin_can_update_any_note_regardless_of_creator(): void
    {
        $creator = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $application = $this->applicationForBranch($creator->branch_id);
        $note = ApplicationNote::factory()->create([
            'application_id' => $application->id,
            'user_id' => $creator->id,
            'content' => 'Nội dung cũ',
        ]);

        $this->actingAs($admin)
            ->put(route('hr.applications.notes.update', [$application, $note]), ['content' => 'Admin sửa'])
            ->assertRedirect(route('hr.applications.show', $application));

        $this->assertSame('Admin sửa', $note->fresh()->content);
    }

    public function test_creator_loses_update_rights_after_application_transferred_to_another_branch(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);
        $note = ApplicationNote::factory()->create([
            'application_id' => $application->id,
            'user_id' => $staff->id,
            'content' => 'Nội dung cũ',
        ]);

        // Mo phong Application da bi Admin transfer sang co so khac (route rieng, ngoai pham vi
        // task nay) — chi can owner_branch_id doi la du de kiem tra Branch Policy tai thoi diem
        // sua Note.
        $application->update(['owner_branch_id' => Branch::factory()->create()->id]);

        $this->actingAs($staff)
            ->put(route('hr.applications.notes.update', [$application, $note]), ['content' => 'Sửa sau khi chuyển'])
            ->assertForbidden();

        $this->assertSame('Nội dung cũ', $note->fresh()->content);
    }

    public function test_creator_can_soft_delete_own_note(): void
    {
        $staff = User::factory()->create();
        $application = $this->applicationForBranch($staff->branch_id);
        $note = ApplicationNote::factory()->create([
            'application_id' => $application->id,
            'user_id' => $staff->id,
        ]);

        $response = $this->actingAs($staff)->delete(route('hr.applications.notes.destroy', [$application, $note]));

        $response->assertRedirect(route('hr.applications.show', $application));
        $this->assertSoftDeleted('application_notes', ['id' => $note->id]);
    }

    public function test_another_staff_of_same_branch_cannot_delete_someone_elses_note(): void
    {
        $creator = User::factory()->create();
        $otherStaff = User::factory()->create(['branch_id' => $creator->branch_id]);
        $application = $this->applicationForBranch($creator->branch_id);
        $note = ApplicationNote::factory()->create([
            'application_id' => $application->id,
            'user_id' => $creator->id,
        ]);

        $this->actingAs($otherStaff)
            ->delete(route('hr.applications.notes.destroy', [$application, $note]))
            ->assertForbidden();

        $this->assertDatabaseHas('application_notes', ['id' => $note->id, 'deleted_at' => null]);
    }

    public function test_no_restore_route_exists_for_application_notes_in_phase_1(): void
    {
        $this->assertFalse(Route::has('hr.applications.notes.restore'));
    }
}
