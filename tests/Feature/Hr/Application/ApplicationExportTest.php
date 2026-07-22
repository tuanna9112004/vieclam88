<?php

namespace Tests\Feature\Hr\Application;

use App\Models\Application;
use App\Models\Branch;
use App\Models\ExportLog;
use App\Models\Job;
use App\Models\User;
use App\Support\CsvSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_sanitizer_escapes_potential_formula_injection_characters(): void
    {
        $this->assertSame("'=1+2", CsvSanitizer::escape("=1+2"));
        $this->assertSame("'+CMD", CsvSanitizer::escape("+CMD"));
        $this->assertSame("'-calc", CsvSanitizer::escape("-calc"));
        $this->assertSame("'@SUM(A1)", CsvSanitizer::escape("@SUM(A1)"));
        $this->assertSame("Nguyễn Văn A", CsvSanitizer::escape("Nguyễn Văn A"));
        $this->assertSame("", CsvSanitizer::escape(null));
    }

    public function test_staff_export_is_strictly_scoped_to_their_own_branch(): void
    {
        $branchStaff = Branch::factory()->create(['status' => 'active']);
        $branchOther = Branch::factory()->create(['status' => 'active']);

        $staff = User::factory()->create(['branch_id' => $branchStaff->id, 'role' => 'staff']);

        $jobStaff = Job::factory()->create(['owner_branch_id' => $branchStaff->id]);
        $jobOther = Job::factory()->create(['owner_branch_id' => $branchOther->id]);

        $appStaff = Application::factory()->create([
            'job_id' => $jobStaff->id,
            'owner_branch_id' => $branchStaff->id,
            'submitted_full_name' => 'Ứng Viên Cơ Sở Mình',
        ]);

        $appOther = Application::factory()->create([
            'job_id' => $jobOther->id,
            'owner_branch_id' => $branchOther->id,
            'submitted_full_name' => 'Ứng Viên Cơ Sở Khác',
        ]);

        $response = $this->actingAs($staff)->get(route('hr.applications.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        // Staff sees their own application, but not other branch's application
        $this->assertStringContainsString('Ứng Viên Cơ Sở Mình', $content);
        $this->assertStringNotContainsString('Ứng Viên Cơ Sở Khác', $content);

        // Verify export_logs table created 1 record
        $this->assertDatabaseHas('export_logs', [
            'exported_by' => $staff->id,
            'export_type' => 'applications_csv',
            'row_count' => 1,
        ]);
    }

    public function test_admin_export_can_see_all_branches_and_filters(): void
    {
        $branch1 = Branch::factory()->create(['status' => 'active']);
        $branch2 = Branch::factory()->create(['status' => 'active']);

        $admin = User::factory()->admin()->create();

        $app1 = Application::factory()->create([
            'owner_branch_id' => $branch1->id,
            'submitted_full_name' => 'Ứng Viên Chi Nhánh 1',
        ]);

        $app2 = Application::factory()->create([
            'owner_branch_id' => $branch2->id,
            'submitted_full_name' => 'Ứng Viên Chi Nhánh 2',
        ]);

        $response = $this->actingAs($admin)->get(route('hr.applications.export'));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Ứng Viên Chi Nhánh 1', $content);
        $this->assertStringContainsString('Ứng Viên Chi Nhánh 2', $content);

        // Verify export_logs table recorded row_count = 2
        $this->assertSame(1, ExportLog::where('exported_by', $admin->id)->count());
        $log = ExportLog::where('exported_by', $admin->id)->first();
        $this->assertSame('applications_csv', $log->export_type);
        $this->assertSame(2, $log->row_count);
    }

    public function test_export_escapes_csv_formula_injection_in_output(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create(['status' => 'active']);

        $maliciousApp = Application::factory()->create([
            'owner_branch_id' => $branch->id,
            'submitted_full_name' => '=1+2',
            'submitted_phone' => '+84988777666',
        ]);

        $response = $this->actingAs($admin)->get(route('hr.applications.export'));

        $response->assertOk();
        $content = $response->streamedContent();

        // Formula characters should be escaped with a single quote prefix
        $this->assertStringContainsString("'=1+2", $content);
        $this->assertStringContainsString("'+84988777666", $content);
    }
}
