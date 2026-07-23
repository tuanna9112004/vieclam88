<?php

namespace Tests\Feature\Hr\Job;

use App\Models\AdministrativeUnit;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyContact;
use App\Models\CompanyLocation;
use App\Models\IndustrialPark;
use App\Models\Job;
use App\Models\JobLocation;
use App\Models\JobVerification;
use App\Models\JobWorkShift;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishJobActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tạo 1 Job thỏa mãn toàn bộ 22 điều kiện Publish Predicate — dùng làm baseline, mỗi test
     * chỉ phá đúng 1 điều kiện rồi xác nhận bị từ chối, các điều kiện khác giữ nguyên hợp lệ.
     */
    protected function makePublishableJob(array $jobOverrides = [], array $locationOverrides = []): Job
    {
        $company = Company::factory()->create(['status' => 'active']);
        $branch = Branch::factory()->create(['status' => 'active', 'phone' => '0900000000', 'zalo' => null]);
        $adminUnit = AdministrativeUnit::factory()->create();

        $location = CompanyLocation::factory()->create(array_merge([
            'company_id' => $company->id,
            'status' => 'active',
            'administrative_unit_id' => $adminUnit->id,
            'industrial_park_id' => null,
        ], $locationOverrides));

        $job = Job::factory()->create(array_merge([
            'company_id' => $company->id,
            'owner_branch_id' => $branch->id,
            'status' => 'draft',
            'title' => 'Công nhân sản xuất',
            'job_description' => 'Mô tả công việc đầy đủ, rõ ràng.',
            'requirements' => 'Tốt nghiệp THPT, chăm chỉ.',
            'benefits' => 'Bảo hiểm đầy đủ, thưởng lễ tết.',
            'salary_period' => 'negotiable',
        ], $jobOverrides));

        JobLocation::factory()->create([
            'job_id' => $job->id,
            'company_location_id' => $location->id,
            'is_primary' => true,
        ]);

        $shift = WorkShift::factory()->create();
        JobWorkShift::factory()->create(['job_id' => $job->id, 'work_shift_id' => $shift->id]);

        JobVerification::factory()->create([
            'job_id' => $job->id,
            'result' => 'still_open',
            'verified_at' => now(),
        ]);

        return $job->fresh();
    }

    protected function publish(Job $job, ?User $actor = null, ?string $overrideReason = null)
    {
        $actor ??= User::factory()->create(['branch_id' => $job->owner_branch_id]);

        return $this->actingAs($actor)->post(route('hr.jobs.publish', $job), array_filter([
            'verification_override_reason' => $overrideReason,
        ]));
    }

    // --- happy path ---

    public function test_publish_succeeds_when_all_conditions_met(): void
    {
        $job = $this->makePublishableJob();

        $response = $this->publish($job);

        $response->assertRedirect(route('hr.jobs.index'));
        $job->refresh();
        $this->assertSame('published', $job->status);
        $this->assertNotNull($job->published_at);
        $this->assertDatabaseHas('job_status_histories', [
            'job_id' => $job->id,
            'from_status' => 'draft',
            'to_status' => 'published',
        ]);
    }

    public function test_publish_succeeds_reopening_from_paused(): void
    {
        $job = $this->makePublishableJob(['status' => 'paused']);

        $response = $this->publish($job);

        $response->assertRedirect(route('hr.jobs.index'));
        $this->assertSame('published', $job->fresh()->status);
        $this->assertDatabaseHas('job_status_histories', [
            'job_id' => $job->id,
            'from_status' => 'paused',
            'to_status' => 'published',
        ]);
    }

    public function test_reopen_rechecks_predicate_and_rejects_if_company_no_longer_active(): void
    {
        $job = $this->makePublishableJob(['status' => 'paused']);
        $job->company()->update(['status' => 'hidden']);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('company');
        $this->assertSame('paused', $job->fresh()->status);
    }

    // --- dieu kien 3 ---

    public function test_publish_rejects_when_status_is_published(): void
    {
        $job = $this->makePublishableJob(['status' => 'published']);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('status');
    }

    public function test_publish_rejects_when_status_is_closed(): void
    {
        $job = $this->makePublishableJob(['status' => 'closed']);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('status');
    }

    // --- dieu kien 4-5 ---

    public function test_publish_rejects_inactive_company(): void
    {
        $job = $this->makePublishableJob();
        $job->company()->update(['status' => 'hidden']);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('company');
        $this->assertSame('draft', $job->fresh()->status);
    }

    public function test_publish_rejects_deleted_company(): void
    {
        $job = $this->makePublishableJob();
        $job->company->delete();

        $response = $this->publish($job);

        $response->assertSessionHasErrors('company');
    }

    // --- dieu kien 7-9 ---

    public function test_publish_rejects_inactive_branch(): void
    {
        $job = $this->makePublishableJob();
        $job->ownerBranch()->update(['status' => 'inactive']);

        $response = $this->publish($job, User::factory()->superAdmin()->create());

        $response->assertSessionHasErrors('owner_branch_id');
    }

    public function test_publish_rejects_deleted_branch(): void
    {
        $job = $this->makePublishableJob();
        $job->ownerBranch->delete();

        $response = $this->publish($job, User::factory()->superAdmin()->create());

        $response->assertSessionHasErrors('owner_branch_id');
    }

    public function test_publish_rejects_branch_without_phone_or_zalo(): void
    {
        $job = $this->makePublishableJob();
        $job->ownerBranch()->update(['phone' => null, 'zalo' => null]);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('owner_branch_cta');
    }

    public function test_publish_allows_branch_with_only_zalo(): void
    {
        $job = $this->makePublishableJob();
        $job->ownerBranch()->update(['phone' => null, 'zalo' => 'zalo-cs']);

        $response = $this->publish($job);

        $response->assertRedirect(route('hr.jobs.index'));
    }

    // --- dieu kien 10-13 ---

    public function test_publish_rejects_blank_title(): void
    {
        $job = $this->makePublishableJob(['title' => '   ']);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('title');
    }

    public function test_publish_rejects_blank_job_description(): void
    {
        $job = $this->makePublishableJob(['job_description' => null]);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('job_description');
    }

    public function test_publish_rejects_whitespace_only_requirements(): void
    {
        $job = $this->makePublishableJob(['requirements' => "   \n  "]);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('requirements');
    }

    public function test_publish_rejects_blank_benefits(): void
    {
        $job = $this->makePublishableJob(['benefits' => null]);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('benefits');
    }

    // --- dieu kien 14-18 ---

    public function test_publish_rejects_missing_primary_location(): void
    {
        $job = $this->makePublishableJob();
        JobLocation::where('job_id', $job->id)->update(['is_primary' => false]);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('company_location_id');
    }

    public function test_publish_rejects_primary_location_of_other_company(): void
    {
        $job = $this->makePublishableJob();
        $otherLocation = CompanyLocation::factory()->create(['status' => 'active']);
        JobLocation::where('job_id', $job->id)->update(['company_location_id' => $otherLocation->id]);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('company_location_id');
    }

    public function test_publish_rejects_inactive_primary_location(): void
    {
        $job = $this->makePublishableJob();
        $job->jobLocations()->first()->companyLocation()->update(['status' => 'inactive']);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('company_location_id');
    }

    public function test_publish_rejects_deleted_primary_location(): void
    {
        $job = $this->makePublishableJob();
        $job->jobLocations()->first()->companyLocation->delete();

        $response = $this->publish($job);

        $response->assertSessionHasErrors('company_location_id');
    }

    public function test_publish_rejects_location_without_administrative_unit_or_address(): void
    {
        $job = $this->makePublishableJob([], ['administrative_unit_id' => null, 'address_detail' => null]);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('location_clear');
    }

    public function test_publish_allows_location_with_only_address_detail(): void
    {
        $job = $this->makePublishableJob([], ['administrative_unit_id' => null, 'address_detail' => 'Số 1 đường ABC']);

        $response = $this->publish($job);

        $response->assertRedirect(route('hr.jobs.index'));
    }

    public function test_publish_rejects_inactive_industrial_park(): void
    {
        $unit = AdministrativeUnit::factory()->create();
        $park = IndustrialPark::factory()->inactive()->create(['administrative_unit_id' => $unit->id]);
        $job = $this->makePublishableJob([], [
            'administrative_unit_id' => $unit->id,
            'industrial_park_id' => $park->id,
        ]);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('industrial_park');
    }

    public function test_publish_rejects_industrial_park_administrative_unit_mismatch(): void
    {
        $unitA = AdministrativeUnit::factory()->create();
        $unitB = AdministrativeUnit::factory()->create();
        $park = IndustrialPark::factory()->create(['administrative_unit_id' => $unitB->id]);
        $job = $this->makePublishableJob([], [
            'administrative_unit_id' => $unitA->id,
            'industrial_park_id' => $park->id,
        ]);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('industrial_park');
    }

    public function test_publish_allows_valid_industrial_park(): void
    {
        $unit = AdministrativeUnit::factory()->create();
        $park = IndustrialPark::factory()->create(['administrative_unit_id' => $unit->id]);
        $job = $this->makePublishableJob([], [
            'administrative_unit_id' => $unit->id,
            'industrial_park_id' => $park->id,
        ]);

        $response = $this->publish($job);

        $response->assertRedirect(route('hr.jobs.index'));
    }

    // --- ngoai 22 dieu (ACCEPTANCE-CRITERIA.md): company_contact_id phai con hop le luc publish ---

    public function test_publish_allows_job_with_no_contact_selected(): void
    {
        $job = $this->makePublishableJob(['company_contact_id' => null]);

        $response = $this->publish($job);

        $response->assertRedirect(route('hr.jobs.index'));
    }

    public function test_publish_allows_active_contact_of_same_company(): void
    {
        $job = $this->makePublishableJob();
        $contact = CompanyContact::factory()->create(['company_id' => $job->company_id, 'status' => 'active']);
        $job->update(['company_contact_id' => $contact->id]);

        $response = $this->publish($job);

        $response->assertRedirect(route('hr.jobs.index'));
    }

    public function test_publish_rejects_contact_that_became_inactive_after_being_attached(): void
    {
        $job = $this->makePublishableJob();
        $contact = CompanyContact::factory()->create(['company_id' => $job->company_id, 'status' => 'active']);
        $job->update(['company_contact_id' => $contact->id]);
        $contact->update(['status' => 'inactive']);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('company_contact_id');
        $this->assertSame('draft', $job->fresh()->status);
    }

    public function test_publish_rejects_soft_deleted_contact(): void
    {
        $job = $this->makePublishableJob();
        $contact = CompanyContact::factory()->create(['company_id' => $job->company_id, 'status' => 'active']);
        $job->update(['company_contact_id' => $contact->id]);
        $contact->delete();

        $response = $this->publish($job);

        $response->assertSessionHasErrors('company_contact_id');
    }

    public function test_publish_rejects_contact_belonging_to_other_company(): void
    {
        $job = $this->makePublishableJob();
        $otherContact = CompanyContact::factory()->create(['status' => 'active']);
        // Gan truc tiep qua DB, bo qua guard cua Store/UpdateJobRequest — mo phong tinh huong
        // du lieu da lech (defense-in-depth), khong phai duong di binh thuong qua form.
        $job->update(['company_contact_id' => $otherContact->id]);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('company_contact_id');
    }

    // --- dieu kien 19 - PUB-SALARY (2 mode loai tru nhau, docs/CORE-FLOWS.md + ACCEPTANCE-CRITERIA.md) ---

    public function test_publish_allows_pure_negotiable_with_no_numeric_salary(): void
    {
        $job = $this->makePublishableJob([
            'salary_period' => 'negotiable',
            'salary_min' => null,
            'salary_max' => null,
            'salary_base' => null,
        ]);

        $response = $this->publish($job);

        $response->assertRedirect(route('hr.jobs.index'));
    }

    public function test_publish_rejects_negotiable_combined_with_leftover_numeric_salary(): void
    {
        // docs/CORE-FLOWS.md + docs/ACCEPTANCE-CRITERIA.md (2/3 nguon): negotiable bat buoc
        // moi cot luong so deu NULL — khong duoc ket hop.
        $job = $this->makePublishableJob([
            'salary_period' => 'negotiable',
            'salary_min' => 5000000,
        ]);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('salary');
    }

    public function test_publish_rejects_when_no_salary_information_at_all(): void
    {
        $job = $this->makePublishableJob([
            'salary_period' => 'month',
            'salary_min' => null,
            'salary_max' => null,
            'salary_base' => null,
            'salary_description' => null,
        ]);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('salary');
    }

    public function test_publish_rejects_zero_salary_amount_without_description(): void
    {
        // "So luong duong" (ACCEPTANCE-CRITERIA.md) — 0 khong duoc tinh la co thong tin luong.
        $job = $this->makePublishableJob([
            'salary_period' => 'month',
            'salary_min' => 0,
            'salary_description' => null,
        ]);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('salary');
    }

    public function test_publish_allows_positive_numeric_salary(): void
    {
        $job = $this->makePublishableJob([
            'salary_period' => 'month',
            'salary_min' => 8000000,
            'salary_max' => 12000000,
        ]);

        $response = $this->publish($job);

        $response->assertRedirect(route('hr.jobs.index'));
    }

    public function test_publish_allows_salary_description_only(): void
    {
        $job = $this->makePublishableJob([
            'salary_period' => 'month',
            'salary_min' => null,
            'salary_max' => null,
            'salary_base' => null,
            'salary_description' => 'Thỏa thuận theo năng lực',
        ]);

        $response = $this->publish($job);

        $response->assertRedirect(route('hr.jobs.index'));
    }

    // --- dieu kien 20 - PUB-SHIFT ---

    public function test_publish_rejects_when_no_work_shifts(): void
    {
        $job = $this->makePublishableJob();
        JobWorkShift::where('job_id', $job->id)->delete();

        $response = $this->publish($job);

        $response->assertSessionHasErrors('job_work_shifts');
    }

    // --- dieu kien 21 - PUB-VERIFY ---

    public function test_publish_rejects_when_never_verified(): void
    {
        $job = $this->makePublishableJob();
        JobVerification::where('job_id', $job->id)->delete();

        $response = $this->publish($job);

        $response->assertSessionHasErrors('verification');
    }

    public function test_publish_rejects_when_latest_verification_is_not_still_open(): void
    {
        $job = $this->makePublishableJob();
        // Ban ghi still_open cu KHONG duoc dung lam bang chung (ADR-058) — chi ban ghi moi nhat.
        JobVerification::factory()->create([
            'job_id' => $job->id,
            'result' => 'needs_review',
            'verified_at' => now()->addMinute(),
        ]);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('verification');
    }

    public function test_staff_cannot_override_missing_verification(): void
    {
        $job = $this->makePublishableJob();
        JobVerification::where('job_id', $job->id)->delete();
        $staff = User::factory()->create(['branch_id' => $job->owner_branch_id]);

        $response = $this->publish($job, $staff, 'Bỏ qua vì lý do X');

        $response->assertSessionHasErrors('verification');
        $this->assertSame('draft', $job->fresh()->status);
    }

    public function test_admin_can_override_missing_verification_with_reason(): void
    {
        $job = $this->makePublishableJob();
        JobVerification::where('job_id', $job->id)->delete();
        $admin = User::factory()->admin()->create();

        $response = $this->publish($job, $admin, 'Đã xác nhận qua điện thoại, chưa kịp ghi hệ thống');

        $response->assertRedirect(route('hr.jobs.index'));
        $this->assertSame('published', $job->fresh()->status);
        $this->assertDatabaseHas('job_status_histories', [
            'job_id' => $job->id,
            'to_status' => 'published',
            'reason' => 'Đã xác nhận qua điện thoại, chưa kịp ghi hệ thống',
        ]);
    }

    public function test_admin_override_requires_non_empty_reason(): void
    {
        $job = $this->makePublishableJob();
        JobVerification::where('job_id', $job->id)->delete();
        $admin = User::factory()->admin()->create();

        $response = $this->publish($job, $admin, null);

        $response->assertSessionHasErrors('verification');
        $this->assertSame('draft', $job->fresh()->status);
    }

    public function test_verification_freshness_check_applies_when_setting_configured(): void
    {
        Setting::query()->updateOrCreate(['key' => 'job_verification_valid_days'], ['value' => '3', 'type' => 'integer']);

        $job = $this->makePublishableJob();
        JobVerification::where('job_id', $job->id)->update(['verified_at' => now()->subDays(10)]);

        $response = $this->publish($job);

        $response->assertSessionHasErrors('verification');
    }

    // --- dieu kien 22 - authorization ---

    public function test_staff_cannot_publish_job_of_other_branch(): void
    {
        $job = $this->makePublishableJob();
        $otherStaff = User::factory()->create();

        $response = $this->publish($job, $otherStaff);

        $response->assertForbidden();
        $this->assertSame('draft', $job->fresh()->status);
    }

    public function test_admin_can_publish_job_of_any_branch(): void
    {
        $job = $this->makePublishableJob();
        $admin = User::factory()->admin()->create();

        $response = $this->publish($job, $admin);

        $response->assertRedirect(route('hr.jobs.index'));
    }

    // --- transaction/history integrity ---

    public function test_failed_predicate_leaves_no_status_history_and_status_unchanged(): void
    {
        $job = $this->makePublishableJob(['title' => '']);

        $this->publish($job);

        $this->assertSame('draft', $job->fresh()->status);
        $this->assertDatabaseCount('job_status_histories', 0);
    }
}
