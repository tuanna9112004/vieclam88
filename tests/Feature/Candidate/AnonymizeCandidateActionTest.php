<?php

namespace Tests\Feature\Candidate;

use App\Actions\Application\ReopenApplicationAction;
use App\Actions\Candidate\AnonymizeCandidateAction;
use App\Actions\Candidate\MergeCandidateAction;
use App\Models\Application;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\CandidateContact;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AnonymizeCandidateActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_anonymize_candidate_policy(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create(['role' => 'staff']);
        $candidate = Candidate::factory()->create();

        $this->assertTrue($admin->can('anonymize', $candidate));
        $this->assertFalse($staff->can('anonymize', $candidate));
    }

    public function test_anonymize_masks_all_pii_fields_across_candidate_contacts_and_applications(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create(['status' => 'active']);

        $candidate = Candidate::factory()->create([
            'full_name' => 'Nguyễn Văn Định Danh',
            'date_of_birth' => '1995-05-15',
            'address_detail' => 'Số 123 Đường Lê Lợi, TP. Bắc Ninh',
            'status' => 'active',
        ]);

        $contact = CandidateContact::factory()->create([
            'candidate_id' => $candidate->id,
            'type' => 'phone',
            'value' => '0988777666',
            'normalized_value' => '0988777666',
        ]);

        $job = Job::factory()->create(['owner_branch_id' => $branch->id]);

        $application = Application::factory()->create([
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'owner_branch_id' => $branch->id,
            'submitted_full_name' => 'Nguyễn Văn Định Danh',
            'submitted_phone' => '0988777666',
            'submitted_phone_normalized' => '0988777666',
            'submission_snapshot' => [
                'full_name' => 'Nguyễn Văn Định Danh',
                'phone' => '0988777666',
                'date_of_birth' => '1995-05-15',
                'education_level' => 'Đại học',
            ],
            'consent_ip' => '127.0.0.1',
            'consent_user_agent' => 'Mozilla/5.0 (Windows NT 10.0)',
            'stage' => 'contacting',
        ]);

        $action = new AnonymizeCandidateAction();
        $action->handle($candidate, $admin);

        // 1. Verify Candidates PII masked
        $freshCand = $candidate->fresh();
        $this->assertSame('[ĐÃ ẨN DANH]', $freshCand->full_name);
        $this->assertSame('đã ẩn danh', $freshCand->full_name_normalized);
        $this->assertNull($freshCand->date_of_birth);
        $this->assertNull($freshCand->address_detail);
        $this->assertSame('anonymized', $freshCand->status);
        $this->assertNotNull($freshCand->anonymized_at);
        $this->assertSame($admin->id, $freshCand->anonymized_by);

        // 2. Verify CandidateContacts PII masked
        $freshContact = $contact->fresh();
        $this->assertStringStartsWith('0000000000', $freshContact->value);
        $this->assertFalse($freshContact->is_active);

        // 3. Verify Applications PII masked per ADR-056
        $freshApp = $application->fresh();
        $this->assertSame('[ĐÃ ẨN DANH]', $freshApp->submitted_full_name);
        $this->assertSame('0000000000', $freshApp->submitted_phone);
        $this->assertSame('0000000000', $freshApp->submitted_phone_normalized);
        $this->assertNull($freshApp->consent_ip);
        $this->assertNull($freshApp->consent_user_agent);

        // Submission snapshot PII redacted while keeping non-PII keys
        $snapshot = $freshApp->submission_snapshot;
        $this->assertSame('[ĐÃ ẨN DANH]', $snapshot['full_name']);
        $this->assertSame('0000000000', $snapshot['phone']);
        $this->assertNull($snapshot['date_of_birth']);
        $this->assertSame('Đại học', $snapshot['education_level']);

        // Audit & Business structure retained
        $this->assertSame('contacting', $freshApp->stage);
        $this->assertSame($job->id, $freshApp->job_id);
    }

    public function test_re_anonymizing_already_anonymized_candidate_fails(): void
    {
        $admin = User::factory()->admin()->create();
        $candidate = Candidate::factory()->create(['status' => 'anonymized']);

        $action = new AnonymizeCandidateAction();

        $this->expectException(ValidationException::class);
        $action->handle($candidate, $admin);
    }

    public function test_anonymized_candidate_cannot_be_updated_or_merged(): void
    {
        $admin = User::factory()->admin()->create();
        $anonymized = Candidate::factory()->create(['status' => 'anonymized']);
        $target = Candidate::factory()->create(['status' => 'active']);

        // 1. CandidatePolicy::update returns false
        $this->assertFalse($admin->can('update', $anonymized));

        // 2. MergeCandidateAction rejects anonymized candidate
        $mergeAction = new MergeCandidateAction();
        $this->expectException(ValidationException::class);
        $mergeAction->handle($anonymized, $target, $admin, 'Thử merge ẩn danh');
    }

    public function test_reopening_closed_application_of_anonymized_candidate_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $candidate = Candidate::factory()->create(['status' => 'anonymized']);
        $job = Job::factory()->create(['status' => 'published']);

        $closedApp = Application::factory()->create([
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'stage' => 'closed',
            'close_reason' => 'unreachable',
            'closed_at' => now()->subDay(),
        ]);

        $reopenAction = new ReopenApplicationAction();

        $this->expectException(ValidationException::class);
        $reopenAction->handle($closedApp, 'new', $admin, 'Mở lại hồ sơ đã ẩn danh');
    }
}
