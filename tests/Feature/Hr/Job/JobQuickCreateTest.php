<?php

namespace Tests\Feature\Hr\Job;

use App\Models\Company;
use App\Models\CompanyContact;
use App\Models\CompanyLocation;
use App\Models\Job;
use App\Models\JobLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobQuickCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_create_page_renders_with_company_and_location_fields(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->get(route('hr.jobs.create'));

        $response->assertOk();
        $response->assertSee('company_location_id', false);
        $response->assertSee('toggle-new-company', false);
        $response->assertSee('toggle-new-location', false);
    }

    public function test_job_edit_page_renders_with_prefilled_location(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id]);
        $location = CompanyLocation::factory()->create(['company_id' => $job->company_id]);
        JobLocation::factory()->create(['job_id' => $job->id, 'company_location_id' => $location->id, 'is_primary' => true]);

        $response = $this->actingAs($staff)->get(route('hr.jobs.edit', $job));

        $response->assertOk();
    }

    public function test_company_store_returns_json_when_ajax_requested(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->postJson(route('hr.companies.store'), [
            'name' => 'Công ty AJAX',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['id', 'name']);
        $response->assertJson(['name' => 'Công ty AJAX']);
        $this->assertDatabaseHas('companies', ['name' => 'Công ty AJAX']);
    }

    public function test_company_store_still_redirects_for_normal_form_submission(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->post(route('hr.companies.store'), [
            'name' => 'Công ty Form Thường',
        ]);

        $response->assertRedirect(route('hr.companies.index'));
    }

    public function test_company_location_index_returns_json_list_filtered_by_company(): void
    {
        $staff = User::factory()->create();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        CompanyLocation::factory()->create(['company_id' => $companyA->id, 'name' => 'Nhà máy A1']);
        CompanyLocation::factory()->create(['company_id' => $companyB->id, 'name' => 'Nhà máy B1']);

        $response = $this->actingAs($staff)->getJson(route('hr.company-locations.index', $companyA));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Nhà máy A1']);
        $response->assertJsonMissing(['name' => 'Nhà máy B1']);
    }

    public function test_company_location_store_returns_json_when_ajax_requested(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($staff)->postJson(route('hr.company-locations.store', $company), [
            'name' => 'Nhà máy AJAX',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['id', 'name']);
        $this->assertDatabaseHas('company_locations', ['company_id' => $company->id, 'name' => 'Nhà máy AJAX']);
    }

    public function test_creating_job_with_new_company_and_location_via_quick_create_flow_without_leaving_screen(): void
    {
        $staff = User::factory()->create();

        // 1. Tren man hinh tao Job, bam "Tao cong ty moi" -> AJAX, khong roi man hinh.
        $companyResponse = $this->actingAs($staff)->postJson(route('hr.companies.store'), [
            'name' => 'Công ty Ngay Trong Job',
        ]);
        $companyId = $companyResponse->json('id');

        // 2. Bam "Tao dia diem moi" cho cong ty vua tao -> AJAX, khong roi man hinh.
        $locationResponse = $this->actingAs($staff)->postJson(
            route('hr.company-locations.store', $companyId),
            ['name' => 'Nhà máy Ngay Trong Job']
        );
        $locationId = $locationResponse->json('id');

        // 3. Submit form Job that voi company/location vua tao qua AJAX o buoc 1-2.
        $jobResponse = $this->actingAs($staff)->post(route('hr.jobs.store'), [
            'title' => 'Công nhân Quick Create',
            'company_id' => $companyId,
            'company_location_id' => $locationId,
        ]);

        $jobResponse->assertRedirect(route('hr.jobs.index'));

        $job = Job::where('title', 'Công nhân Quick Create')->firstOrFail();
        $this->assertSame($companyId, $job->company_id);
        $this->assertDatabaseHas('job_locations', [
            'job_id' => $job->id,
            'company_location_id' => $locationId,
            'is_primary' => true,
        ]);
    }

    public function test_job_location_must_belong_to_selected_company(): void
    {
        $staff = User::factory()->create();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $locationOfB = CompanyLocation::factory()->create(['company_id' => $companyB->id]);

        $response = $this->actingAs($staff)->post(route('hr.jobs.store'), [
            'title' => 'Công nhân Sai Địa Điểm',
            'company_id' => $companyA->id,
            'company_location_id' => $locationOfB->id,
        ]);

        $response->assertSessionHasErrors('company_location_id');
        $this->assertDatabaseMissing('jobs', ['title' => 'Công nhân Sai Địa Điểm']);
    }

    public function test_deleting_location_used_by_job_is_blocked(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $location = CompanyLocation::factory()->create(['company_id' => $company->id]);
        $job = Job::factory()->create(['company_id' => $company->id]);
        JobLocation::factory()->create(['job_id' => $job->id, 'company_location_id' => $location->id, 'is_primary' => true]);

        $response = $this->actingAs($admin)->delete(route('hr.company-locations.destroy', [$company, $location]));

        $response->assertStatus(422);
        $this->assertDatabaseHas('company_locations', ['id' => $location->id, 'deleted_at' => null]);
    }

    public function test_job_can_be_created_with_contact_belonging_to_same_company(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();
        $contact = CompanyContact::factory()->create(['company_id' => $company->id, 'status' => 'active']);

        $response = $this->actingAs($staff)->post(route('hr.jobs.store'), [
            'title' => 'Công nhân Có Đầu Mối',
            'company_id' => $company->id,
            'company_contact_id' => $contact->id,
        ]);

        $response->assertRedirect(route('hr.jobs.index'));
        $this->assertDatabaseHas('jobs', ['title' => 'Công nhân Có Đầu Mối', 'company_contact_id' => $contact->id]);
    }

    public function test_job_contact_must_belong_to_selected_company(): void
    {
        $staff = User::factory()->create();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $contactOfB = CompanyContact::factory()->create(['company_id' => $companyB->id, 'status' => 'active']);

        $response = $this->actingAs($staff)->post(route('hr.jobs.store'), [
            'title' => 'Công nhân Sai Đầu Mối',
            'company_id' => $companyA->id,
            'company_contact_id' => $contactOfB->id,
        ]);

        $response->assertSessionHasErrors('company_contact_id');
        $this->assertDatabaseMissing('jobs', ['title' => 'Công nhân Sai Đầu Mối']);
    }

    public function test_job_rejects_inactive_contact(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();
        $contact = CompanyContact::factory()->create(['company_id' => $company->id, 'status' => 'inactive']);

        $response = $this->actingAs($staff)->post(route('hr.jobs.store'), [
            'title' => 'Công nhân Đầu Mối Ngừng Hoạt Động',
            'company_id' => $company->id,
            'company_contact_id' => $contact->id,
        ]);

        $response->assertSessionHasErrors('company_contact_id');
        $this->assertDatabaseMissing('jobs', ['title' => 'Công nhân Đầu Mối Ngừng Hoạt Động']);
    }

    public function test_company_contact_index_returns_only_active_contacts_of_that_company(): void
    {
        $staff = User::factory()->create();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        CompanyContact::factory()->create(['company_id' => $companyA->id, 'name' => 'Chị Lan', 'status' => 'active']);
        CompanyContact::factory()->create(['company_id' => $companyA->id, 'name' => 'Anh Nam', 'status' => 'inactive']);
        CompanyContact::factory()->create(['company_id' => $companyB->id, 'name' => 'Chị Hoa', 'status' => 'active']);

        $response = $this->actingAs($staff)->getJson(route('hr.company-contacts.index', $companyA));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Chị Lan']);
        $response->assertJsonMissing(['name' => 'Anh Nam']);
        $response->assertJsonMissing(['name' => 'Chị Hoa']);
    }

    public function test_updating_job_can_clear_selected_contact(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();
        $contact = CompanyContact::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $job = Job::factory()->create([
            'owner_branch_id' => $staff->branch_id,
            'company_id' => $company->id,
            'company_contact_id' => $contact->id,
        ]);

        $this->actingAs($staff)->put(route('hr.jobs.update', $job), [
            'title' => $job->title,
            'company_id' => $job->company_id,
            'company_contact_id' => '',
        ]);

        $this->assertNull($job->fresh()->company_contact_id);
    }

    public function test_updating_job_location_selection_replaces_old_one(): void
    {
        $staff = User::factory()->create();
        $job = Job::factory()->create(['owner_branch_id' => $staff->branch_id]);
        $oldLocation = CompanyLocation::factory()->create(['company_id' => $job->company_id]);
        $newLocation = CompanyLocation::factory()->create(['company_id' => $job->company_id]);
        JobLocation::factory()->create(['job_id' => $job->id, 'company_location_id' => $oldLocation->id, 'is_primary' => true]);

        $this->actingAs($staff)->put(route('hr.jobs.update', $job), [
            'title' => $job->title,
            'company_id' => $job->company_id,
            'company_location_id' => $newLocation->id,
        ]);

        $this->assertDatabaseMissing('job_locations', ['job_id' => $job->id, 'company_location_id' => $oldLocation->id]);
        $this->assertDatabaseHas('job_locations', [
            'job_id' => $job->id,
            'company_location_id' => $newLocation->id,
            'is_primary' => true,
        ]);
    }
}
