<?php

namespace Tests\Feature\Hr\Job;

use App\Models\Application;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyContact;
use App\Models\CompanyLocation;
use App\Models\Job;
use App\Models\JobLocation;
use App\Models\JobStatusHistory;
use App\Models\JobVerification;
use App\Models\JobWorkShift;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobDuplicateDeleteRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_duplicate_own_branch_job_with_only_allowed_business_data(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();
        $contact = CompanyContact::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $location = CompanyLocation::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $shift = WorkShift::factory()->create(['is_active' => true]);
        $source = Job::factory()->create([
            'company_id' => $company->id,
            'company_contact_id' => $contact->id,
            'owner_branch_id' => $staff->branch_id,
            'title' => 'Công nhân lắp ráp',
            'employment_type' => 'seasonal',
            'quantity' => 50,
            'salary_min' => 12_000_000,
            'salary_max' => 15_000_000,
            'job_description' => 'Mô tả được phép sao chép.',
            'has_shuttle_bus' => true,
            'shuttle_bus_details' => 'Tuyến xe Bắc Ninh.',
            'is_urgent' => true,
            'status' => 'published',
            'published_at' => now()->subDays(10),
            'expires_at' => now()->addMonth(),
            'closed_at' => now()->subDay(),
            'close_reason' => 'other',
            'last_checked_at' => now()->subDay(),
            'last_verified_at' => now()->subDay(),
        ]);
        JobLocation::create([
            'job_id' => $source->id,
            'company_location_id' => $location->id,
            'is_primary' => true,
        ]);
        JobWorkShift::create([
            'job_id' => $source->id,
            'work_shift_id' => $shift->id,
            'description' => 'Ca ngày',
        ]);
        JobStatusHistory::create([
            'job_id' => $source->id,
            'from_status' => 'draft',
            'to_status' => 'published',
            'reason' => null,
            'changed_by' => $staff->id,
        ]);
        JobVerification::create([
            'job_id' => $source->id,
            'verified_by' => $staff->id,
            'result' => 'still_open',
            'note' => null,
            'verified_at' => now(),
        ]);
        Application::factory()->create([
            'job_id' => $source->id,
            'owner_branch_id' => $source->owner_branch_id,
        ]);
        $otherBranch = Branch::factory()->create(['status' => 'active']);

        $response = $this->actingAs($staff)->post(route('hr.jobs.duplicate', $source), [
            'owner_branch_id' => $otherBranch->id,
            'status' => 'published',
            'code' => 'CLIENT-CODE',
        ]);

        $duplicate = Job::query()->whereKeyNot($source->id)->latest('id')->firstOrFail();

        $response->assertRedirect(route('hr.jobs.edit', $duplicate));
        $this->assertNotSame($source->public_id, $duplicate->public_id);
        $this->assertNotSame($source->code, $duplicate->code);
        $this->assertNotSame($source->slug, $duplicate->slug);
        $this->assertNotSame('CLIENT-CODE', $duplicate->code);
        $this->assertSame($source->owner_branch_id, $duplicate->owner_branch_id);
        $this->assertNotSame($otherBranch->id, $duplicate->owner_branch_id);
        $this->assertSame($staff->id, $duplicate->created_by);
        $this->assertSame('draft', $duplicate->status);
        $this->assertSame($source->title, $duplicate->title);
        $this->assertSame('seasonal', $duplicate->employment_type->value);
        $this->assertSame(50, $duplicate->quantity);
        $this->assertSame(12_000_000, $duplicate->salary_min);
        $this->assertSame('Mô tả được phép sao chép.', $duplicate->job_description);
        $this->assertTrue($duplicate->has_shuttle_bus);
        $this->assertTrue($duplicate->is_urgent);
        $this->assertNull($duplicate->published_at);
        $this->assertNull($duplicate->expires_at);
        $this->assertNull($duplicate->closed_at);
        $this->assertNull($duplicate->close_reason);
        $this->assertNull($duplicate->last_checked_at);
        $this->assertNull($duplicate->last_verified_at);
        $this->assertSame(1, $duplicate->jobLocations()->count());
        $this->assertDatabaseHas('job_locations', [
            'job_id' => $duplicate->id,
            'company_location_id' => $location->id,
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('job_work_shifts', [
            'job_id' => $duplicate->id,
            'work_shift_id' => $shift->id,
            'description' => 'Ca ngày',
        ]);
        $this->assertSame(0, $duplicate->jobStatusHistories()->count());
        $this->assertSame(0, $duplicate->jobVerifications()->count());
        $this->assertSame(0, $duplicate->applications()->count());
        $this->assertSame(1, $duplicate->jobBranchHistories()->count());
        $this->assertDatabaseHas('job_branch_histories', [
            'job_id' => $duplicate->id,
            'from_branch_id' => null,
            'to_branch_id' => $source->owner_branch_id,
            'changed_by' => $staff->id,
        ]);
    }

    public function test_staff_cannot_duplicate_other_branch_but_admin_can(): void
    {
        $staff = User::factory()->create();
        $otherBranch = Branch::factory()->create(['status' => 'active']);
        $source = Job::factory()->create(['owner_branch_id' => $otherBranch->id]);

        $this->actingAs($staff)
            ->post(route('hr.jobs.duplicate', $source))
            ->assertForbidden();

        $this->assertDatabaseCount('jobs', 1);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->post(route('hr.jobs.duplicate', $source))
            ->assertRedirect();

        $duplicate = Job::query()->whereKeyNot($source->id)->firstOrFail();
        $this->assertSame($otherBranch->id, $duplicate->owner_branch_id);
        $this->assertSame('draft', $duplicate->status);
    }

    public function test_duplicate_returns_business_error_when_reference_is_no_longer_valid(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();
        $location = CompanyLocation::factory()->create(['company_id' => $company->id]);
        $source = Job::factory()->create([
            'company_id' => $company->id,
            'owner_branch_id' => $staff->branch_id,
        ]);
        JobLocation::create([
            'job_id' => $source->id,
            'company_location_id' => $location->id,
            'is_primary' => true,
        ]);
        $location->delete();

        $response = $this->actingAs($staff)->post(route('hr.jobs.duplicate', $source));

        $response->assertSessionHasErrors('job');
        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_admin_can_soft_delete_draft_and_closed_jobs_and_deleted_by_is_recorded(): void
    {
        $admin = User::factory()->admin()->create();
        $draft = Job::factory()->create(['status' => 'draft']);
        $closed = Job::factory()->create(['status' => 'closed']);

        $this->actingAs($admin)
            ->delete(route('hr.jobs.destroy', $draft))
            ->assertRedirect(route('hr.jobs.index'));
        $this->actingAs($admin)
            ->delete(route('hr.jobs.destroy', $closed))
            ->assertRedirect(route('hr.jobs.index'));

        $this->assertSoftDeleted('jobs', ['id' => $draft->id, 'deleted_by' => $admin->id]);
        $this->assertSoftDeleted('jobs', ['id' => $closed->id, 'deleted_by' => $admin->id]);
    }

    public function test_delete_rejects_unclosed_job_and_job_with_application_without_500(): void
    {
        $admin = User::factory()->admin()->create();
        $published = Job::factory()->create(['status' => 'published']);
        $draftWithApplication = Job::factory()->create(['status' => 'draft']);
        Application::factory()->create([
            'job_id' => $draftWithApplication->id,
            'owner_branch_id' => $draftWithApplication->owner_branch_id,
        ]);

        $this->actingAs($admin)
            ->delete(route('hr.jobs.destroy', $published))
            ->assertSessionHasErrors('job');
        $this->actingAs($admin)
            ->delete(route('hr.jobs.destroy', $draftWithApplication))
            ->assertSessionHasErrors('job');

        $this->assertDatabaseHas('jobs', ['id' => $published->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('jobs', ['id' => $draftWithApplication->id, 'deleted_at' => null]);
    }

    public function test_staff_cannot_delete_or_restore_job(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id, 'status' => 'draft']);

        $this->actingAs($staff)
            ->delete(route('hr.jobs.destroy', $job))
            ->assertForbidden();

        $job->delete();

        $this->actingAs($staff)
            ->post(route('hr.jobs.restore', $job))
            ->assertForbidden();

        $this->assertSoftDeleted('jobs', ['id' => $job->id]);
    }

    public function test_admin_can_restore_job_when_identity_and_references_remain_valid(): void
    {
        $admin = User::factory()->admin()->create();
        $job = Job::factory()->create(['status' => 'draft']);
        $originalCode = $job->code;
        $originalSlug = $job->slug;

        $this->actingAs($admin)->delete(route('hr.jobs.destroy', $job))->assertRedirect();
        $this->assertSoftDeleted('jobs', ['id' => $job->id]);

        $response = $this->actingAs($admin)->post(route('hr.jobs.restore', $job));

        $response->assertRedirect(route('hr.jobs.index'));
        $this->assertDatabaseHas('jobs', [
            'id' => $job->id,
            'code' => $originalCode,
            'slug' => $originalSlug,
            'deleted_at' => null,
            'updated_by' => $admin->id,
        ]);
    }

    public function test_restore_rejects_deleted_company_inactive_branch_and_deleted_reference(): void
    {
        $admin = User::factory()->admin()->create();

        $companyJob = Job::factory()->create(['status' => 'draft']);
        $this->actingAs($admin)->delete(route('hr.jobs.destroy', $companyJob));
        $companyJob->company->delete();
        $this->actingAs($admin)
            ->post(route('hr.jobs.restore', $companyJob))
            ->assertSessionHasErrors('job');
        $this->assertSoftDeleted('jobs', ['id' => $companyJob->id]);

        $branchJob = Job::factory()->create(['status' => 'draft']);
        $this->actingAs($admin)->delete(route('hr.jobs.destroy', $branchJob));
        $branchJob->ownerBranch->update(['status' => 'inactive']);
        $this->actingAs($admin)
            ->post(route('hr.jobs.restore', $branchJob))
            ->assertSessionHasErrors('job');
        $this->assertSoftDeleted('jobs', ['id' => $branchJob->id]);

        $company = Company::factory()->create();
        $location = CompanyLocation::factory()->create(['company_id' => $company->id]);
        $referenceJob = Job::factory()->create(['company_id' => $company->id, 'status' => 'draft']);
        JobLocation::create([
            'job_id' => $referenceJob->id,
            'company_location_id' => $location->id,
            'is_primary' => true,
        ]);
        $this->actingAs($admin)->delete(route('hr.jobs.destroy', $referenceJob));
        $location->delete();
        $this->actingAs($admin)
            ->post(route('hr.jobs.restore', $referenceJob))
            ->assertSessionHasErrors('job');
        $this->assertSoftDeleted('jobs', ['id' => $referenceJob->id]);
    }

    public function test_index_shows_clear_duplicate_delete_and_restore_confirmations(): void
    {
        $admin = User::factory()->admin()->create();
        $activeJob = Job::factory()->create(['status' => 'draft']);
        $deletedJob = Job::factory()->create(['status' => 'draft']);
        $deletedJob->delete();

        $response = $this->actingAs($admin)->get(route('hr.jobs.index'));

        $response->assertOk()
            ->assertSee(route('hr.jobs.duplicate', $activeJob), false)
            ->assertSee('Hồ sơ, lịch sử và xác minh sẽ không được sao chép.', false)
            ->assertSee(route('hr.jobs.destroy', $activeJob), false)
            ->assertSee('Xóa mềm Job', false)
            ->assertSee(route('hr.jobs.restore', $deletedJob), false)
            ->assertSee('kiểm tra lại toàn bộ mã, slug và tham chiếu', false);
    }

    public function test_guest_is_redirected_from_all_three_routes(): void
    {
        $job = Job::factory()->create(['status' => 'draft']);

        $this->post(route('hr.jobs.duplicate', $job))->assertRedirect(route('hr.login'));
        $this->delete(route('hr.jobs.destroy', $job))->assertRedirect(route('hr.login'));

        $job->delete();
        $this->post(route('hr.jobs.restore', $job))->assertRedirect(route('hr.login'));
    }
}
