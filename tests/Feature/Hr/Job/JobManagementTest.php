<?php

namespace Tests\Feature\Hr\Job;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_job_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('hr.jobs.index'))->assertOk();
    }

    public function test_staff_can_view_job_index(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('hr.jobs.index'))->assertOk();
    }

    public function test_guest_is_redirected_from_job_index(): void
    {
        $this->get(route('hr.jobs.index'))->assertRedirect(route('hr.login'));
    }

    public function test_staff_can_create_job_draft_auto_assigned_to_own_branch(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($staff)->post(route('hr.jobs.store'), [
            'title' => 'Công nhân may',
            'company_id' => $company->id,
        ]);

        $response->assertRedirect(route('hr.jobs.index'));

        $job = Job::where('title', 'Công nhân may')->firstOrFail();
        $this->assertSame($staff->branch_id, $job->owner_branch_id);
        $this->assertSame($staff->id, $job->created_by);
        $this->assertSame('draft', $job->status);
    }

    public function test_staff_cannot_choose_owner_branch_when_creating(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();
        $otherBranch = Branch::factory()->create(['status' => 'active']);

        $this->actingAs($staff)->post(route('hr.jobs.store'), [
            'title' => 'Công nhân đóng gói',
            'company_id' => $company->id,
            'owner_branch_id' => $otherBranch->id,
        ]);

        $job = Job::where('title', 'Công nhân đóng gói')->firstOrFail();
        $this->assertSame($staff->branch_id, $job->owner_branch_id);
        $this->assertNotSame($otherBranch->id, $job->owner_branch_id);
    }

    public function test_admin_must_choose_owner_branch_when_creating(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($admin)->post(route('hr.jobs.store'), [
            'title' => 'Công nhân kỹ thuật',
            'company_id' => $company->id,
        ]);

        $response->assertSessionHasErrors('owner_branch_id');
        $this->assertDatabaseMissing('jobs', ['title' => 'Công nhân kỹ thuật']);
    }

    public function test_admin_can_create_job_for_any_branch(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin)->post(route('hr.jobs.store'), [
            'title' => 'Công nhân vận hành',
            'company_id' => $company->id,
            'owner_branch_id' => $branch->id,
        ]);

        $response->assertRedirect(route('hr.jobs.index'));
        $this->assertDatabaseHas('jobs', ['title' => 'Công nhân vận hành', 'owner_branch_id' => $branch->id]);
    }

    public function test_creating_job_requires_title_and_company(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->post(route('hr.jobs.store'), []);

        $response->assertSessionHasErrors(['title', 'company_id']);
    }

    public function test_creating_job_rejects_soft_deleted_company(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();
        $company->delete();

        $response = $this->actingAs($staff)->post(route('hr.jobs.store'), [
            'title' => 'Công nhân kho',
            'company_id' => $company->id,
        ]);

        $response->assertSessionHasErrors('company_id');
    }

    public function test_job_draft_created_with_minimal_fields_has_null_optional_fields(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($staff)->post(route('hr.jobs.store'), [
            'title' => 'Công nhân lắp ráp',
            'company_id' => $company->id,
        ]);

        $job = Job::where('title', 'Công nhân lắp ráp')->firstOrFail();

        $this->assertNull($job->job_description);
        $this->assertNull($job->requirements);
        $this->assertNull($job->benefits);
        $this->assertNull($job->salary_min);
    }

    public function test_code_slug_public_id_are_server_generated(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($staff)->post(route('hr.jobs.store'), [
            'title' => 'Công nhân sản xuất',
            'company_id' => $company->id,
            'code' => 'CLIENT-CODE',
            'slug' => 'client-slug',
            'public_id' => 'client-public-id',
            'status' => 'published',
            'created_by' => 999,
        ]);

        $job = Job::where('title', 'Công nhân sản xuất')->firstOrFail();

        $this->assertNotSame('CLIENT-CODE', $job->code);
        $this->assertNotSame('client-slug', $job->slug);
        $this->assertNotSame('client-public-id', $job->public_id);
        $this->assertSame(26, strlen($job->public_id));
        $this->assertSame('draft', $job->status);
        $this->assertSame($staff->id, $job->created_by);
    }

    public function test_staff_can_update_job_in_own_branch(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id]);

        $response = $this->actingAs($staff)->put(route('hr.jobs.update', $job), [
            'title' => 'Tên mới',
            'company_id' => $job->company_id,
        ]);

        $response->assertRedirect(route('hr.jobs.index'));
        $this->assertSame('Tên mới', $job->fresh()->title);
        $this->assertSame($staff->id, $job->fresh()->updated_by);
    }

    public function test_staff_cannot_update_job_in_other_branch(): void
    {
        $staff = User::factory()->create();
        $otherBranch = Branch::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $otherBranch->id]);

        $response = $this->actingAs($staff)->put(route('hr.jobs.update', $job), [
            'title' => 'Bi doi ten sai',
            'company_id' => $job->company_id,
        ]);

        $response->assertForbidden();
        $this->assertNotSame('Bi doi ten sai', $job->fresh()->title);
    }

    public function test_admin_can_update_any_job(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $branch->id]);

        $response = $this->actingAs($admin)->put(route('hr.jobs.update', $job), [
            'title' => 'Ten moi boi admin',
            'company_id' => $job->company_id,
        ]);

        $response->assertRedirect(route('hr.jobs.index'));
        $this->assertSame('Ten moi boi admin', $job->fresh()->title);
    }

    public function test_updating_job_cannot_change_owner_branch_id(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create(['status' => 'active']);
        $job = Job::factory()->create(['owner_branch_id' => $branch->id]);

        $this->actingAs($admin)->put(route('hr.jobs.update', $job), [
            'title' => $job->title,
            'company_id' => $job->company_id,
            'owner_branch_id' => $otherBranch->id,
        ]);

        $this->assertSame($branch->id, $job->fresh()->owner_branch_id);
    }
}
