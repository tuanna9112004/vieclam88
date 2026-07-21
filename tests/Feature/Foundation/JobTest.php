<?php

namespace Tests\Feature\Foundation;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyContact;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JobTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_id_must_be_unique(): void
    {
        Job::factory()->create(['public_id' => 'DUP-PUBLIC-ID']);

        $this->expectException(QueryException::class);

        Job::factory()->create(['public_id' => 'DUP-PUBLIC-ID']);
    }

    public function test_code_must_be_unique(): void
    {
        Job::factory()->create(['code' => 'JOB-DUP']);

        $this->expectException(QueryException::class);

        Job::factory()->create(['code' => 'JOB-DUP']);
    }

    public function test_slug_must_be_unique(): void
    {
        Job::factory()->create(['slug' => 'viec-lam-abc']);

        $this->expectException(QueryException::class);

        Job::factory()->create(['slug' => 'viec-lam-abc']);
    }

    public function test_company_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        Job::factory()->create(['company_id' => null]);
    }

    public function test_deleting_company_referenced_by_job_is_restricted(): void
    {
        $company = Company::factory()->create();
        Job::factory()->create(['company_id' => $company->id]);

        $this->expectException(QueryException::class);

        $company->forceDelete();
    }

    public function test_owner_branch_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        Job::factory()->create(['owner_branch_id' => null]);
    }

    public function test_deleting_branch_referenced_by_job_is_restricted(): void
    {
        $branch = Branch::factory()->create();
        Job::factory()->create(['owner_branch_id' => $branch->id]);

        $this->expectException(QueryException::class);

        $branch->forceDelete();
    }

    public function test_created_by_is_required(): void
    {
        $this->expectException(QueryException::class);

        Job::factory()->create(['created_by' => null]);
    }

    public function test_deleting_creator_user_is_restricted(): void
    {
        $admin = User::factory()->admin()->create();
        Job::factory()->create(['created_by' => $admin->id]);

        $this->expectException(QueryException::class);

        $admin->delete();
    }

    public function test_deleting_updater_user_sets_updated_by_null(): void
    {
        $creator = User::factory()->admin()->create();
        $updater = User::factory()->admin()->create();
        $job = Job::factory()->create(['created_by' => $creator->id, 'updated_by' => $updater->id]);

        $updater->delete();

        $this->assertNull($job->fresh()->updated_by);
    }

    public function test_deleting_deleter_user_sets_deleted_by_null(): void
    {
        $creator = User::factory()->admin()->create();
        $deleter = User::factory()->admin()->create();
        $job = Job::factory()->create(['created_by' => $creator->id, 'deleted_by' => $deleter->id]);

        $deleter->delete();

        $this->assertNull($job->fresh()->deleted_by);
    }

    public function test_company_contact_id_is_nullable(): void
    {
        $job = Job::factory()->create(['company_contact_id' => null]);

        $this->assertNull($job->fresh()->company_contact_id);
    }

    public function test_deleting_company_contact_sets_company_contact_id_null(): void
    {
        // CompanyContact::delete() la soft delete (khong cham FK) — dung forceDelete() de kiem
        // tra dung rang buoc SET NULL o tang DB.
        $company = Company::factory()->create();
        $contact = CompanyContact::factory()->create(['company_id' => $company->id]);
        $job = Job::factory()->create(['company_id' => $company->id, 'company_contact_id' => $contact->id]);

        $contact->forceDelete();

        $this->assertNull($job->fresh()->company_contact_id);
    }

    public function test_status_defaults_to_draft(): void
    {
        // Insert thẳng qua query builder, không qua factory, để xác nhận DB tự điền default.
        $company = Company::factory()->create();
        $branch = Branch::factory()->create();
        $admin = User::factory()->admin()->create();

        $id = DB::table('jobs')->insertGetId([
            'public_id' => 'default-status-test',
            'company_id' => $company->id,
            'owner_branch_id' => $branch->id,
            'code' => 'JOB-DEFAULT',
            'title' => 'Viec lam mac dinh',
            'slug' => 'viec-lam-mac-dinh',
            'created_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('jobs', [
            'id' => $id,
            'status' => 'draft',
            'salary_period' => 'month',
            'currency' => 'VND',
            'employment_type' => 'full_time',
        ]);
    }

    public function test_min_age_must_not_exceed_max_age(): void
    {
        $this->expectException(QueryException::class);

        Job::factory()->create(['min_age' => 30, 'max_age' => 20]);
    }

    public function test_min_age_equal_max_age_is_allowed(): void
    {
        $job = Job::factory()->create(['min_age' => 25, 'max_age' => 25]);

        $this->assertSame(25, $job->fresh()->min_age);
    }

    public function test_salary_min_must_not_exceed_salary_max(): void
    {
        $this->expectException(QueryException::class);

        Job::factory()->create(['salary_min' => 20000000, 'salary_max' => 10000000]);
    }

    public function test_salary_min_equal_salary_max_is_allowed(): void
    {
        $job = Job::factory()->create(['salary_min' => 10000000, 'salary_max' => 10000000]);

        $this->assertSame(10000000, $job->fresh()->salary_min);
    }

    public function test_soft_delete_keeps_job_row(): void
    {
        $job = Job::factory()->create();

        $job->delete();

        $this->assertSoftDeleted('jobs', ['id' => $job->id]);
        $this->assertDatabaseHas('jobs', ['id' => $job->id]);
    }

    public function test_restoring_a_soft_deleted_job(): void
    {
        $job = Job::factory()->create();
        $job->delete();

        $job->restore();

        $this->assertDatabaseHas('jobs', ['id' => $job->id, 'deleted_at' => null]);
    }

    public function test_belongs_to_company_and_owner_branch(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create();
        $job = Job::factory()->create(['company_id' => $company->id, 'owner_branch_id' => $branch->id]);

        $this->assertTrue($job->company->is($company));
        $this->assertTrue($job->ownerBranch->is($branch));
    }
}
