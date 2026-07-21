<?php

namespace Tests\Feature\Hr\Job;

use App\Models\Branch;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobPauseCloseTest extends TestCase
{
    use RefreshDatabase;

    protected function actor(Job $job): User
    {
        return User::factory()->create(['branch_id' => $job->owner_branch_id]);
    }

    // --- pause: published -> paused ---

    public function test_pause_transitions_published_job_to_paused(): void
    {
        $job = Job::factory()->create(['status' => 'published']);
        $staff = $this->actor($job);

        $response = $this->actingAs($staff)->post(route('hr.jobs.pause', $job), ['reason' => 'Hết nhu cầu tạm thời']);

        $response->assertRedirect(route('hr.jobs.index'));
        $job->refresh();
        $this->assertSame('paused', $job->status);
        $this->assertDatabaseHas('job_status_histories', [
            'job_id' => $job->id,
            'from_status' => 'published',
            'to_status' => 'paused',
            'reason' => 'Hết nhu cầu tạm thời',
        ]);
    }

    public function test_pause_works_without_reason(): void
    {
        $job = Job::factory()->create(['status' => 'published']);
        $staff = $this->actor($job);

        $response = $this->actingAs($staff)->post(route('hr.jobs.pause', $job));

        $response->assertRedirect(route('hr.jobs.index'));
        $this->assertSame('paused', $job->fresh()->status);
    }

    public function test_staff_cannot_pause_job_of_other_branch(): void
    {
        $otherBranch = Branch::factory()->create();
        $job = Job::factory()->create(['status' => 'published', 'owner_branch_id' => $otherBranch->id]);
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->post(route('hr.jobs.pause', $job));

        $response->assertForbidden();
        $this->assertSame('published', $job->fresh()->status);
    }

    public function test_admin_can_pause_job_of_any_branch(): void
    {
        $job = Job::factory()->create(['status' => 'published']);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('hr.jobs.pause', $job));

        $response->assertRedirect(route('hr.jobs.index'));
        $this->assertSame('paused', $job->fresh()->status);
    }

    // --- close: published|paused -> closed ---

    public function test_close_transitions_published_job_to_closed(): void
    {
        $job = Job::factory()->create(['status' => 'published']);
        $staff = $this->actor($job);

        $response = $this->actingAs($staff)->post(route('hr.jobs.close', $job), ['close_reason' => 'recruitment_filled']);

        $response->assertRedirect(route('hr.jobs.index'));
        $job->refresh();
        $this->assertSame('closed', $job->status);
        $this->assertSame('recruitment_filled', $job->close_reason->value);
        $this->assertNotNull($job->closed_at);
        $this->assertDatabaseHas('job_status_histories', [
            'job_id' => $job->id,
            'from_status' => 'published',
            'to_status' => 'closed',
            'reason' => 'recruitment_filled',
        ]);
    }

    public function test_close_transitions_paused_job_to_closed(): void
    {
        $job = Job::factory()->create(['status' => 'paused']);
        $staff = $this->actor($job);

        $response = $this->actingAs($staff)->post(route('hr.jobs.close', $job), ['close_reason' => 'company_request']);

        $response->assertRedirect(route('hr.jobs.index'));
        $this->assertSame('closed', $job->fresh()->status);
    }

    public function test_close_requires_close_reason(): void
    {
        $job = Job::factory()->create(['status' => 'published']);
        $staff = $this->actor($job);

        $response = $this->actingAs($staff)->post(route('hr.jobs.close', $job), []);

        $response->assertSessionHasErrors('close_reason');
        $this->assertSame('published', $job->fresh()->status);
    }

    public function test_close_rejects_invalid_close_reason_value(): void
    {
        $job = Job::factory()->create(['status' => 'published']);
        $staff = $this->actor($job);

        $response = $this->actingAs($staff)->post(route('hr.jobs.close', $job), ['close_reason' => 'khong_hop_le']);

        $response->assertSessionHasErrors('close_reason');
    }

    public function test_staff_cannot_close_job_of_other_branch(): void
    {
        $otherBranch = Branch::factory()->create();
        $job = Job::factory()->create(['status' => 'published', 'owner_branch_id' => $otherBranch->id]);
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->post(route('hr.jobs.close', $job), ['close_reason' => 'other']);

        $response->assertForbidden();
        $this->assertSame('published', $job->fresh()->status);
    }

    // --- transition matrix: invalid transitions bi tu choi ---

    public function test_cannot_pause_a_draft_job(): void
    {
        $job = Job::factory()->create(['status' => 'draft']);
        $staff = $this->actor($job);

        $response = $this->actingAs($staff)->post(route('hr.jobs.pause', $job));

        $response->assertSessionHasErrors('status');
        $this->assertSame('draft', $job->fresh()->status);
    }

    public function test_cannot_close_a_draft_job(): void
    {
        $job = Job::factory()->create(['status' => 'draft']);
        $staff = $this->actor($job);

        $response = $this->actingAs($staff)->post(route('hr.jobs.close', $job), ['close_reason' => 'other']);

        $response->assertSessionHasErrors('status');
        $this->assertSame('draft', $job->fresh()->status);
    }

    public function test_cannot_pause_an_already_paused_job(): void
    {
        $job = Job::factory()->create(['status' => 'paused']);
        $staff = $this->actor($job);

        $response = $this->actingAs($staff)->post(route('hr.jobs.pause', $job));

        $response->assertSessionHasErrors('status');
        $this->assertSame('paused', $job->fresh()->status);
    }

    public function test_cannot_pause_or_close_a_closed_job(): void
    {
        $job = Job::factory()->create(['status' => 'closed']);
        $staff = $this->actor($job);

        $pauseResponse = $this->actingAs($staff)->post(route('hr.jobs.pause', $job));
        $closeResponse = $this->actingAs($staff)->post(route('hr.jobs.close', $job), ['close_reason' => 'other']);

        $pauseResponse->assertSessionHasErrors('status');
        $closeResponse->assertSessionHasErrors('status');
        $this->assertSame('closed', $job->fresh()->status);
    }

    public function test_no_history_created_when_transition_is_rejected(): void
    {
        $job = Job::factory()->create(['status' => 'closed']);
        $staff = $this->actor($job);

        $this->actingAs($staff)->post(route('hr.jobs.pause', $job));

        $this->assertDatabaseCount('job_status_histories', 0);
    }
}
