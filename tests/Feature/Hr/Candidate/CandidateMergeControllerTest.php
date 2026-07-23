<?php

namespace Tests\Feature\Hr\Candidate;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateMergeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected(): void
    {
        $source = Candidate::factory()->create();
        $target = Candidate::factory()->create();

        $this->post(route('hr.candidates.merge', $source), [
            'target_candidate_id' => $target->id, 'reason' => 'trung lap',
        ])->assertRedirect(route('hr.login'));
    }

    public function test_staff_is_forbidden(): void
    {
        $staff = User::factory()->create();
        $source = Candidate::factory()->create();
        $target = Candidate::factory()->create();

        $this->actingAs($staff)->post(route('hr.candidates.merge', $source), [
            'target_candidate_id' => $target->id, 'reason' => 'trung lap',
        ])->assertForbidden();

        $this->assertSame('active', $source->fresh()->status);
    }

    public function test_admin_can_merge_and_is_redirected_to_target(): void
    {
        $admin = User::factory()->admin()->create();
        $source = Candidate::factory()->create();
        $target = Candidate::factory()->create();

        $this->actingAs($admin)->post(route('hr.candidates.merge', $source), [
            'target_candidate_id' => $target->id, 'reason' => 'Cung so dien thoai va ten.',
        ])->assertRedirect(route('hr.candidates.show', $target));

        $fresh = $source->fresh();
        $this->assertSame('merged', $fresh->status);
        $this->assertSame($target->id, $fresh->merged_into_candidate_id);
        $this->assertSame('Cung so dien thoai va ten.', $fresh->merge_reason);
    }

    public function test_reason_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        $source = Candidate::factory()->create();
        $target = Candidate::factory()->create();

        $this->actingAs($admin)->post(route('hr.candidates.merge', $source), [
            'target_candidate_id' => $target->id, 'reason' => '',
        ])->assertSessionHasErrors('reason');

        $this->assertSame('active', $source->fresh()->status);
    }

    public function test_target_must_exist(): void
    {
        $admin = User::factory()->admin()->create();
        $source = Candidate::factory()->create();

        $this->actingAs($admin)->post(route('hr.candidates.merge', $source), [
            'target_candidate_id' => 999999, 'reason' => 'trung lap',
        ])->assertSessionHasErrors('target_candidate_id');
    }

    public function test_cannot_merge_candidate_into_itself(): void
    {
        $admin = User::factory()->admin()->create();
        $candidate = Candidate::factory()->create();

        $this->actingAs($admin)->post(route('hr.candidates.merge', $candidate), [
            'target_candidate_id' => $candidate->id, 'reason' => 'trung lap',
        ])->assertSessionHasErrors('source_candidate_id');

        $this->assertSame('active', $candidate->fresh()->status);
    }

    public function test_cannot_merge_creating_a_cycle(): void
    {
        $admin = User::factory()->admin()->create();
        $candidateA = Candidate::factory()->create();
        $candidateB = Candidate::factory()->create();

        // A -> B da merge truoc do (B van "active" nen con dung duoc lam source o buoc sau).
        $candidateA->update(['status' => 'merged', 'merged_into_candidate_id' => $candidateB->id]);
        // Bay gio thu merge B vao A (nguoc lai) -> tao vong lap.
        $this->actingAs($admin)->post(route('hr.candidates.merge', $candidateB), [
            'target_candidate_id' => $candidateA->id, 'reason' => 'thu tao vong lap',
        ])->assertSessionHasErrors('target_candidate_id');
    }

    public function test_cannot_merge_an_already_merged_source_again(): void
    {
        $admin = User::factory()->admin()->create();
        $root = Candidate::factory()->create();
        $alreadyMerged = Candidate::factory()->create([
            'status' => 'merged', 'merged_into_candidate_id' => $root->id,
        ]);
        $anotherTarget = Candidate::factory()->create();

        $this->actingAs($admin)->post(route('hr.candidates.merge', $alreadyMerged), [
            'target_candidate_id' => $anotherTarget->id, 'reason' => 'thu merge lai',
        ])->assertSessionHasErrors('source_candidate_id');
    }

    public function test_cannot_merge_an_anonymized_candidate(): void
    {
        $admin = User::factory()->admin()->create();
        $anonymized = Candidate::factory()->create(['status' => 'anonymized', 'anonymized_at' => now()]);
        $target = Candidate::factory()->create();

        $this->actingAs($admin)->post(route('hr.candidates.merge', $anonymized), [
            'target_candidate_id' => $target->id, 'reason' => 'thu merge candidate an danh',
        ])->assertSessionHasErrors('source_candidate_id');
    }
}
