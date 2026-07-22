<?php

namespace Tests\Feature\Candidate;

use App\Actions\Application\ReopenApplicationAction;
use App\Actions\Candidate\MergeCandidateAction;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MergeCandidateActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_merge_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $candidate = Candidate::factory()->create();

        $action = new MergeCandidateAction();

        $this->expectException(ValidationException::class);
        $action->handle($candidate, $candidate, $admin, 'Gộp cùng candidate');
    }

    public function test_empty_reason_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $source = Candidate::factory()->create();
        $target = Candidate::factory()->create();

        $action = new MergeCandidateAction();

        $this->expectException(ValidationException::class);
        $action->handle($source, $target, $admin, '   ');
    }

    public function test_already_merged_source_cannot_be_merged_again(): void
    {
        $admin = User::factory()->admin()->create();
        $target = Candidate::factory()->create();
        $source = Candidate::factory()->create([
            'status' => 'merged',
            'merged_into_candidate_id' => $target->id,
        ]);
        $anotherTarget = Candidate::factory()->create();

        $action = new MergeCandidateAction();

        $this->expectException(ValidationException::class);
        $action->handle($source, $anotherTarget, $admin, 'Gộp nguồn đã gộp');
    }

    public function test_anonymized_or_trashed_candidate_cannot_be_merged(): void
    {
        $admin = User::factory()->admin()->create();
        $anonymized = Candidate::factory()->create(['status' => 'anonymized']);
        $target = Candidate::factory()->create();

        $action = new MergeCandidateAction();

        $this->expectException(ValidationException::class);
        $action->handle($anonymized, $target, $admin, 'Gộp ẩn danh');
    }

    public function test_cycle_merge_is_detected_and_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $candA = Candidate::factory()->create();
        $candB = Candidate::factory()->create();

        $action = new MergeCandidateAction();

        // Merge A into B
        $action->handle($candA, $candB, $admin, 'Merge A into B');
        $this->assertSame($candB->id, $candA->fresh()->merged_into_candidate_id);

        // Attempting to merge B into A would create a cycle (B -> A -> B)
        $this->expectException(ValidationException::class);
        $action->handle($candB, $candA, $admin, 'Merge B into A (cycle)');
    }

    public function test_successful_merge_moves_candidate_id_when_no_same_job_conflict(): void
    {
        $admin = User::factory()->admin()->create();
        $source = Candidate::factory()->create(['status' => 'active']);
        $target = Candidate::factory()->create(['status' => 'active']);

        $job1 = Job::factory()->create();
        $job2 = Job::factory()->create();

        // Source has application for Job1, Target has application for Job2 (no overlap)
        $app1 = Application::factory()->create(['candidate_id' => $source->id, 'job_id' => $job1->id]);
        $app2 = Application::factory()->create(['candidate_id' => $target->id, 'job_id' => $job2->id]);

        $action = new MergeCandidateAction();
        $action->handle($source, $target, $admin, 'Trùng thông tin cá nhân');

        $freshSource = $source->fresh();
        $this->assertSame('merged', $freshSource->status);
        $this->assertSame($target->id, $freshSource->merged_into_candidate_id);
        $this->assertSame($admin->id, $freshSource->merged_by);
        $this->assertSame('Trùng thông tin cá nhân', $freshSource->merge_reason);

        // App1 (job1) moved to target
        $this->assertSame($target->id, $app1->fresh()->candidate_id);
        // App2 (job2) remains target
        $this->assertSame($target->id, $app2->fresh()->candidate_id);
    }

    public function test_same_job_conflict_retains_candidate_id_and_closes_duplicate_application(): void
    {
        $admin = User::factory()->admin()->create();
        $source = Candidate::factory()->create(['status' => 'active']);
        $target = Candidate::factory()->create(['status' => 'active']);

        $job = Job::factory()->create(['status' => 'published']);

        // Both source and target applied for the SAME job!
        $appSource = Application::factory()->create([
            'candidate_id' => $source->id,
            'job_id' => $job->id,
            'stage' => 'contacting',
            'workflow_cycle' => 1,
        ]);

        $appTarget = Application::factory()->create([
            'candidate_id' => $target->id,
            'job_id' => $job->id,
            'stage' => 'interview_scheduled',
            'workflow_cycle' => 1,
        ]);

        $action = new MergeCandidateAction();
        // Admin specifies $appTarget to be kept
        $action->handle($source, $target, $admin, 'Gộp 2 ứng viên cùng nộp 1 Job', $appTarget->id);

        // Source candidate status updated
        $this->assertSame('merged', $source->fresh()->status);
        $this->assertSame($target->id, $source->fresh()->merged_into_candidate_id);

        // Crucial check 1: Candidate IDs of both applications remain intact!
        $this->assertSame($source->id, $appSource->fresh()->candidate_id);
        $this->assertSame($target->id, $appTarget->fresh()->candidate_id);

        // Crucial check 2: Kept app remains active in stage
        $this->assertSame('interview_scheduled', $appTarget->fresh()->stage);

        // Crucial check 3: Duplicate app is closed with close_reason = duplicate
        $freshClosed = $appSource->fresh();
        $this->assertSame('closed', $freshClosed->stage);
        $this->assertSame('duplicate', $freshClosed->close_reason);
        $this->assertNotNull($freshClosed->closed_at);

        // Crucial check 4: ApplicationStatusHistory records metadata
        $history = ApplicationStatusHistory::where('application_id', $appSource->id)->first();
        $this->assertNotNull($history);
        $this->assertSame('closed', $history->to_stage);
        $this->assertSame('duplicate', $history->close_reason);
        $this->assertSame($admin->id, $history->changed_by);
        $this->assertSame($appTarget->id, $history->metadata['merge_kept_application_id']);
        $this->assertSame($target->id, $history->metadata['merge_target_candidate_id']);

        // Crucial check 5: Reopening closed duplicate application MUST be rejected!
        $reopenAction = new ReopenApplicationAction();
        $this->expectException(ValidationException::class);
        $reopenAction->handle($appSource, 'new', $admin, 'Cố mở lại hồ sơ duplicate');
    }

    public function test_root_resolution_and_merged_family_ids(): void
    {
        $admin = User::factory()->admin()->create();
        $candA = Candidate::factory()->create();
        $candB = Candidate::factory()->create();
        $candC = Candidate::factory()->create();

        $action = new MergeCandidateAction();

        // 1. Merge A into B
        $action->handle($candA, $candB, $admin, 'Merge A to B');
        // 2. Merge B into C
        $action->handle($candB, $candC, $admin, 'Merge B to C');

        // Verify root resolution
        $this->assertSame($candC->id, $candA->fresh()->resolveRoot()->id);
        $this->assertSame($candC->id, $candB->fresh()->resolveRoot()->id);
        $this->assertSame($candC->id, $candC->fresh()->resolveRoot()->id);

        // Verify merged family IDs
        $familyIds = $candC->fresh()->getMergedFamilyIds();
        sort($familyIds);
        $expected = [$candA->id, $candB->id, $candC->id];
        sort($expected);

        $this->assertSame($expected, $familyIds);
    }
}
