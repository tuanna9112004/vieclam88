<?php

namespace Tests\Feature\Hr\Job;

use App\Models\Branch;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobBranchTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_transfer_draft_job_to_active_branch(): void
    {
        $oldBranch = Branch::factory()->create();
        $newBranch = Branch::factory()->create(['status' => 'active']);
        $job = Job::factory()->create(['status' => 'draft', 'owner_branch_id' => $oldBranch->id]);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('hr.jobs.transfer-branch', $job), [
            'to_branch_id' => $newBranch->id,
            'reason' => 'Tái cấu trúc vận hành',
        ]);

        $response->assertRedirect(route('hr.jobs.index'));
        $job->refresh();
        $this->assertSame($newBranch->id, $job->owner_branch_id);
        $this->assertDatabaseHas('job_branch_histories', [
            'job_id' => $job->id,
            'from_branch_id' => $oldBranch->id,
            'to_branch_id' => $newBranch->id,
            'reason' => 'Tái cấu trúc vận hành',
            'changed_by' => $admin->id,
        ]);
    }

    public function test_admin_can_transfer_paused_job(): void
    {
        $newBranch = Branch::factory()->create(['status' => 'active']);
        $job = Job::factory()->create(['status' => 'paused']);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('hr.jobs.transfer-branch', $job), [
            'to_branch_id' => $newBranch->id,
            'reason' => 'Gan nham co so luc tao',
        ]);

        $response->assertRedirect(route('hr.jobs.index'));
        $this->assertSame($newBranch->id, $job->fresh()->owner_branch_id);
    }

    // --- authorization: chi Admin ---

    public function test_staff_cannot_transfer_branch_even_for_own_branch_job(): void
    {
        $job = Job::factory()->create(['status' => 'draft']);
        $staff = User::factory()->create(['branch_id' => $job->owner_branch_id]);
        $newBranch = Branch::factory()->create(['status' => 'active']);

        $response = $this->actingAs($staff)->post(route('hr.jobs.transfer-branch', $job), [
            'to_branch_id' => $newBranch->id,
            'reason' => 'Ly do bat ky',
        ]);

        $response->assertForbidden();
        $this->assertSame($job->owner_branch_id, $job->fresh()->owner_branch_id);
        $this->assertDatabaseCount('job_branch_histories', 0);
    }

    // --- tu choi published/closed ---

    public function test_rejects_transfer_of_published_job(): void
    {
        $oldBranchId = null;
        $job = Job::factory()->create(['status' => 'published']);
        $oldBranchId = $job->owner_branch_id;
        $newBranch = Branch::factory()->create(['status' => 'active']);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('hr.jobs.transfer-branch', $job), [
            'to_branch_id' => $newBranch->id,
            'reason' => 'Ly do',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertSame($oldBranchId, $job->fresh()->owner_branch_id);
        $this->assertDatabaseCount('job_branch_histories', 0);
    }

    public function test_rejects_transfer_of_closed_job(): void
    {
        $job = Job::factory()->create(['status' => 'closed']);
        $newBranch = Branch::factory()->create(['status' => 'active']);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('hr.jobs.transfer-branch', $job), [
            'to_branch_id' => $newBranch->id,
            'reason' => 'Ly do',
        ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_rejects_transfer_of_soft_deleted_job(): void
    {
        $job = Job::factory()->create(['status' => 'draft']);
        $job->delete();
        $newBranch = Branch::factory()->create(['status' => 'active']);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('hr.jobs.transfer-branch', $job), [
            'to_branch_id' => $newBranch->id,
            'reason' => 'Ly do',
        ]);

        $response->assertNotFound();
    }

    // --- target branch phai active, chua xoa ---

    public function test_rejects_inactive_target_branch(): void
    {
        $job = Job::factory()->create(['status' => 'draft']);
        $inactiveBranch = Branch::factory()->create(['status' => 'inactive']);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('hr.jobs.transfer-branch', $job), [
            'to_branch_id' => $inactiveBranch->id,
            'reason' => 'Ly do',
        ]);

        $response->assertSessionHasErrors('to_branch_id');
    }

    public function test_rejects_deleted_target_branch(): void
    {
        $job = Job::factory()->create(['status' => 'draft']);
        $deletedBranch = Branch::factory()->create(['status' => 'active']);
        $deletedBranch->delete();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('hr.jobs.transfer-branch', $job), [
            'to_branch_id' => $deletedBranch->id,
            'reason' => 'Ly do',
        ]);

        $response->assertSessionHasErrors('to_branch_id');
    }

    public function test_rejects_target_branch_same_as_current(): void
    {
        $branch = Branch::factory()->create(['status' => 'active']);
        $job = Job::factory()->create(['status' => 'draft', 'owner_branch_id' => $branch->id]);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('hr.jobs.transfer-branch', $job), [
            'to_branch_id' => $branch->id,
            'reason' => 'Ly do',
        ]);

        $response->assertSessionHasErrors('to_branch_id');
    }

    // --- reason bat buoc ---

    public function test_reason_is_required(): void
    {
        $job = Job::factory()->create(['status' => 'draft']);
        $newBranch = Branch::factory()->create(['status' => 'active']);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('hr.jobs.transfer-branch', $job), [
            'to_branch_id' => $newBranch->id,
        ]);

        $response->assertSessionHasErrors('reason');
        $this->assertSame($job->owner_branch_id, $job->fresh()->owner_branch_id);
    }

    public function test_reason_rejects_whitespace_only(): void
    {
        $job = Job::factory()->create(['status' => 'draft']);
        $newBranch = Branch::factory()->create(['status' => 'active']);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('hr.jobs.transfer-branch', $job), [
            'to_branch_id' => $newBranch->id,
            'reason' => '   ',
        ]);

        $response->assertSessionHasErrors('reason');
    }
}
