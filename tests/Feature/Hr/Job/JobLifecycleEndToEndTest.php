<?php

namespace Tests\Feature\Hr\Job;

use App\Models\AdministrativeUnit;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\Job;
use App\Models\JobLocation;
use App\Models\JobWorkShift;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gate end-to-end bat buoc (audit Giai doan 5): 1 kich ban lien tuc, dung chung du lieu qua tung
 * buoc, thay vi nhieu test rieng le moi lan tao du lieu moi — phat hien duoc loi "giao thoa" giua
 * cac buoc (vd du lieu con lai tu publish anh huong pause/republish) ma test rieng le khong thay.
 */
class JobLifecycleEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_job_lifecycle_from_draft_to_close_and_branch_transfer(): void
    {
        // 1. Tao Branch A va Branch B active.
        $branchA = Branch::factory()->create(['status' => 'active', 'phone' => '0900000001']);
        $branchB = Branch::factory()->create(['status' => 'active', 'phone' => '0900000002']);

        // 2. Tao Staff A thuoc Branch A.
        $staffA = User::factory()->create(['branch_id' => $branchA->id]);

        // 3+4. Staff A tao Job draft; owner_branch_id gan Branch A du request gia mao Branch B.
        $company = Company::factory()->create(['status' => 'active']);
        $storeResponse = $this->actingAs($staffA)->post(route('hr.jobs.store'), [
            'title' => 'Công nhân vận hành máy',
            'company_id' => $company->id,
            'owner_branch_id' => $branchB->id, // gia mao — phai bi bo qua
        ]);
        $storeResponse->assertRedirect(route('hr.jobs.index'));

        $job = Job::where('title', 'Công nhân vận hành máy')->firstOrFail();
        $this->assertSame($branchA->id, $job->owner_branch_id, 'owner_branch_id phai la Branch A (server-side), khong duoc la Branch B gia mao.');

        // 5. Draft thieu description/requirements/benefits van luu duoc.
        $this->assertNull($job->job_description);
        $this->assertNull($job->requirements);
        $this->assertNull($job->benefits);
        $this->assertSame('draft', $job->status);

        // 6. Location/Contact khac Company bi tu choi.
        $otherCompany = Company::factory()->create(['status' => 'active']);
        $otherCompanyLocation = CompanyLocation::factory()->create(['company_id' => $otherCompany->id, 'status' => 'active']);
        $crossCompanyResponse = $this->actingAs($staffA)->put(route('hr.jobs.update', $job), [
            'title' => $job->title,
            'company_id' => $job->company_id,
            'company_location_id' => $otherCompanyLocation->id,
        ]);
        $crossCompanyResponse->assertSessionHasErrors('company_location_id');

        // Hoan thien du lieu that de sau nay du dieu kien publish.
        $adminUnit = AdministrativeUnit::factory()->create();
        $location = CompanyLocation::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'administrative_unit_id' => $adminUnit->id,
        ]);
        $this->actingAs($staffA)->put(route('hr.jobs.update', $job), [
            'title' => $job->title,
            'company_id' => $job->company_id,
            'company_location_id' => $location->id,
        ]);
        JobLocation::where('job_id', $job->id)->update(['is_primary' => true]);
        $job->update([
            'job_description' => 'Vận hành máy CNC theo quy trình.',
            'requirements' => 'Tốt nghiệp trung cấp kỹ thuật trở lên.',
            'benefits' => 'Bảo hiểm đầy đủ, thưởng năng suất.',
            'salary_period' => 'negotiable',
        ]);

        // 9 (mot phan). Publish khi con thieu dieu kien (chua co ca lam, chua verify) bi tu choi.
        $earlyPublish = $this->actingAs($staffA)->post(route('hr.jobs.publish', $job));
        $earlyPublish->assertSessionHasErrors(['job_work_shifts', 'verification']);
        $this->assertSame('draft', $job->fresh()->status);

        $shift = WorkShift::factory()->create();
        JobWorkShift::factory()->create(['job_id' => $job->id, 'work_shift_id' => $shift->id]);

        // 7. Verification result != still_open: cap nhat last_checked_at, KHONG cap nhat
        // last_verified_at, KHONG publish duoc.
        $needsReviewResponse = $this->actingAs($staffA)->post(route('hr.jobs.verify', $job), ['result' => 'needs_review']);
        $needsReviewResponse->assertRedirect(route('hr.jobs.index'));
        $job->refresh();
        $this->assertNotNull($job->last_checked_at);
        $this->assertNull($job->last_verified_at);

        $stillRejectedPublish = $this->actingAs($staffA)->post(route('hr.jobs.publish', $job));
        $stillRejectedPublish->assertSessionHasErrors('verification');
        $this->assertSame('draft', $job->fresh()->status);

        // 8. Verification still_open hop le: cap nhat ca last_checked_at lan last_verified_at.
        $checkedAtBefore = $job->last_checked_at;
        $stillOpenResponse = $this->actingAs($staffA)->post(route('hr.jobs.verify', $job), ['result' => 'still_open']);
        $stillOpenResponse->assertRedirect(route('hr.jobs.index'));
        $job->refresh();
        $this->assertNotNull($job->last_verified_at);
        $this->assertTrue($job->last_checked_at->gte($checkedAtBefore));

        // 10. Job du Predicate duoc published.
        $publishResponse = $this->actingAs($staffA)->post(route('hr.jobs.publish', $job));
        $publishResponse->assertRedirect(route('hr.jobs.index'));
        $job->refresh();
        $this->assertSame('published', $job->status);
        $this->assertDatabaseHas('job_status_histories', ['job_id' => $job->id, 'from_status' => 'draft', 'to_status' => 'published']);

        // 11. published -> paused thanh cong.
        $pauseResponse = $this->actingAs($staffA)->post(route('hr.jobs.pause', $job), ['reason' => 'Tạm dừng theo yêu cầu công ty']);
        $pauseResponse->assertRedirect(route('hr.jobs.index'));
        $this->assertSame('paused', $job->fresh()->status);

        // 12. paused -> published that bai neu Predicate khong con dat (Company bi an).
        $company->update(['status' => 'hidden']);
        $failedReopen = $this->actingAs($staffA)->post(route('hr.jobs.publish', $job));
        $failedReopen->assertSessionHasErrors('company');
        $this->assertSame('paused', $job->fresh()->status);

        // 13. paused -> published thanh cong sau khi Predicate dat lai.
        $company->update(['status' => 'active']);
        $reopenResponse = $this->actingAs($staffA)->post(route('hr.jobs.publish', $job));
        $reopenResponse->assertRedirect(route('hr.jobs.index'));
        $job->refresh();
        $this->assertSame('published', $job->status);
        $this->assertDatabaseHas('job_status_histories', ['job_id' => $job->id, 'from_status' => 'paused', 'to_status' => 'published']);

        // 14. Close Job tao history dung.
        $closeResponse = $this->actingAs($staffA)->post(route('hr.jobs.close', $job), ['close_reason' => 'recruitment_filled']);
        $closeResponse->assertRedirect(route('hr.jobs.index'));
        $job->refresh();
        $this->assertSame('closed', $job->status);
        $this->assertDatabaseHas('job_status_histories', [
            'job_id' => $job->id,
            'from_status' => 'published',
            'to_status' => 'closed',
            'reason' => 'recruitment_filled',
        ]);

        // --- Rieng cho phan chuyen Branch: dung 1 Job draft/paused khac (Job vua roi da closed,
        // dung lam du lieu cho buoc 18 - tu choi transfer Job closed).

        // 15. Admin chuyen Job draft/paused tu Branch A sang Branch B.
        $admin = User::factory()->admin()->create();
        $transferJob = Job::factory()->create(['status' => 'draft', 'owner_branch_id' => $branchA->id]);

        $transferResponse = $this->actingAs($admin)->post(route('hr.jobs.transfer-branch', $transferJob), [
            'to_branch_id' => $branchB->id,
            'reason' => 'Tái cấu trúc vận hành theo khu vực',
        ]);
        $transferResponse->assertRedirect(route('hr.jobs.index'));
        $transferJob->refresh();
        $this->assertSame($branchB->id, $transferJob->owner_branch_id);
        $this->assertDatabaseHas('job_branch_histories', [
            'job_id' => $transferJob->id,
            'from_branch_id' => $branchA->id,
            'to_branch_id' => $branchB->id,
            'changed_by' => $admin->id,
        ]);

        // 16. Staff A mat quyen truy cap Job (thuoc Branch A) ngay sau khi Job chuyen sang Branch B.
        $staffAAfterTransfer = $this->actingAs($staffA)->put(route('hr.jobs.update', $transferJob), [
            'title' => 'Cố sửa sau khi mất quyền',
            'company_id' => $transferJob->company_id,
        ]);
        $staffAAfterTransfer->assertForbidden();

        // 17. Staff B (thuoc Branch B) co quyen theo dung contract sau khi Job ve Branch B.
        $staffB = User::factory()->create(['branch_id' => $branchB->id]);
        $staffBCanUpdate = $this->actingAs($staffB)->put(route('hr.jobs.update', $transferJob), [
            'title' => 'Staff B đã sửa được',
            'company_id' => $transferJob->company_id,
        ]);
        $staffBCanUpdate->assertRedirect(route('hr.jobs.index'));
        $this->assertSame('Staff B đã sửa được', $transferJob->fresh()->title);

        // 18. Transfer Job published/closed/deleted deu bi tu choi.
        $publishedJob = Job::factory()->create(['status' => 'published', 'owner_branch_id' => $branchA->id]);
        $publishedTransfer = $this->actingAs($admin)->post(route('hr.jobs.transfer-branch', $publishedJob), [
            'to_branch_id' => $branchB->id,
            'reason' => 'Thử chuyển Job đang published',
        ]);
        $publishedTransfer->assertSessionHasErrors('status');

        $closedTransfer = $this->actingAs($admin)->post(route('hr.jobs.transfer-branch', $job), [
            'to_branch_id' => $branchB->id,
            'reason' => 'Thử chuyển Job đã đóng',
        ]);
        $closedTransfer->assertSessionHasErrors('status');

        $deletedJob = Job::factory()->create(['status' => 'draft', 'owner_branch_id' => $branchA->id]);
        $deletedJob->delete();
        $deletedTransfer = $this->actingAs($admin)->post(route('hr.jobs.transfer-branch', $deletedJob), [
            'to_branch_id' => $branchB->id,
            'reason' => 'Thử chuyển Job đã xóa',
        ]);
        $deletedTransfer->assertNotFound();

        // 19. Khong co history hoac du lieu ghi do sau khi cac transfer tren bi tu choi/rollback.
        $this->assertSame($branchA->id, $publishedJob->fresh()->owner_branch_id);
        $this->assertSame('closed', $job->fresh()->status);
        $this->assertDatabaseMissing('job_branch_histories', ['job_id' => $publishedJob->id]);
        $this->assertDatabaseMissing('job_branch_histories', ['job_id' => $job->id]);
        $this->assertDatabaseMissing('job_branch_histories', ['job_id' => $deletedJob->id]);
        // Job chinh va transferJob moi co dung 1 ban ghi lich su cho moi lan doi that su.
        $this->assertDatabaseCount('job_branch_histories', 1);
    }
}
