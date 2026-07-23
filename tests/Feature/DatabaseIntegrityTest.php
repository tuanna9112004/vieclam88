<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationBranchHistory;
use App\Models\ApplicationContactAttempt;
use App\Models\ApplicationStatusHistory;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\ExportLog;
use App\Models\Job;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_26_tables_exist_in_database(): void
    {
        $expectedTables = [
            'administrative_units',
            'branches',
            'users',
            'industrial_parks',
            'companies',
            'company_locations',
            'company_contacts',
            'jobs',
            'job_locations',
            'job_verifications',
            'job_status_histories',
            'job_branch_histories',
            'work_shifts',
            'recruitment_sources',
            'settings',
            'job_work_shifts',
            'candidates',
            'candidate_contacts',
            'applications',
            'candidate_duplicate_reviews',
            'application_status_histories',
            'application_contact_attempts',
            'application_appointments',
            'application_branch_histories',
            'application_notes',
            'export_logs',
        ];

        foreach ($expectedTables as $tableName) {
            $this->assertTrue(Schema::hasTable($tableName), "Thiếu bảng {$tableName} trong CSDL");
        }
    }

    public function test_application_unique_candidate_job_constraint_prevents_duplicate_active_submissions(): void
    {
        $candidate = Candidate::factory()->create();
        $job = Job::factory()->create();

        // First application succeeds
        Application::factory()->create([
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
        ]);

        // Duplicate insert directly to DB throws QueryException
        $this->expectException(QueryException::class);
        Application::factory()->create([
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
        ]);
    }

    public function test_append_only_history_tables_have_timestamps_disabled(): void
    {
        $historyModels = [
            ApplicationStatusHistory::class,
            ApplicationContactAttempt::class,
            ApplicationBranchHistory::class,
            ExportLog::class,
        ];

        foreach ($historyModels as $modelClass) {
            /** @var Model $model */
            $model = new $modelClass;
            $this->assertFalse($model->usesTimestamps(), "Model {$modelClass} phải tắt timestamps để giữ tính append-only");
        }
    }

    public function test_demo_seeder_is_a_no_op_and_preserves_existing_data(): void
    {
        $job = Job::factory()->create(['code' => 'KEEP-JOB-001']);
        $countsBefore = [
            'users' => User::count(),
            'branches' => Branch::count(),
            'companies' => Company::count(),
            'jobs' => Job::count(),
            'candidates' => Candidate::count(),
            'applications' => Application::count(),
        ];

        $this->seed(DemoSeeder::class);

        $this->assertDatabaseHas('jobs', ['id' => $job->id, 'code' => 'KEEP-JOB-001']);
        $this->assertSame($countsBefore, [
            'users' => User::count(),
            'branches' => Branch::count(),
            'companies' => Company::count(),
            'jobs' => Job::count(),
            'candidates' => Candidate::count(),
            'applications' => Application::count(),
        ]);
    }

    public function test_default_database_seeder_creates_only_reference_data_and_no_demo_account(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::count());
        $this->assertSameCanonicalBranchCodes();
        $this->assertSame(0, Company::count());
        $this->assertSame(0, Job::count());
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    private function assertSameCanonicalBranchCodes(): void
    {
        $this->assertSame(
            ['BGBN', 'HB', 'PT', 'VP'],
            Branch::query()->orderBy('code')->pluck('code')->all()
        );
    }

    public function test_demo_seeder_is_fail_closed_outside_local_and_testing_environments(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(\RuntimeException::class);

        $this->app->make(DemoSeeder::class)->run();
    }

    public function test_migration_rollback_and_re_migrate_cycle_runs_cleanly(): void
    {
        $rollbackExitCode = Artisan::call('migrate:rollback');
        $this->assertSame(0, $rollbackExitCode);

        $migrateExitCode = Artisan::call('migrate');
        $this->assertSame(0, $migrateExitCode);
    }
}
