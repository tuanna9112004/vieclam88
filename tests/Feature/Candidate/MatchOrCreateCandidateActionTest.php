<?php

namespace Tests\Feature\Candidate;

use App\Actions\Candidate\MatchOrCreateCandidateAction;
use App\Enums\CandidateDuplicateReviewReason;
use App\Models\Candidate;
use App\Models\CandidateContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchOrCreateCandidateActionTest extends TestCase
{
    use RefreshDatabase;

    private MatchOrCreateCandidateAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = app(MatchOrCreateCandidateAction::class);
    }

    private function createCandidateWithPhone(array $candidateOverrides, string $phoneNormalized): Candidate
    {
        $candidate = Candidate::factory()->create($candidateOverrides);

        CandidateContact::factory()->create([
            'candidate_id' => $candidate->id,
            'type' => 'phone',
            'value' => $phoneNormalized,
            'normalized_value' => $phoneNormalized,
            'is_primary' => true,
        ]);

        return $candidate;
    }

    public function test_no_matching_phone_creates_new_candidate_with_phone_contact(): void
    {
        $result = $this->action->handle('Nguyễn Văn A', '0987654321', '0987654321', null);

        $this->assertTrue($result->isNew);
        $this->assertSame([], $result->suspectedRoots);
        $this->assertSame('Nguyễn Văn A', $result->candidate->full_name);
        $this->assertDatabaseHas('candidate_contacts', [
            'candidate_id' => $result->candidate->id,
            'type' => 'phone',
            'normalized_value' => '0987654321',
            'is_primary' => true,
        ]);
    }

    public function test_exact_match_with_matching_dob_reuses_candidate(): void
    {
        $existing = $this->createCandidateWithPhone([
            'full_name' => 'Nguyễn Văn A',
            'date_of_birth' => '1995-05-20',
        ], '0987654321');

        $result = $this->action->handle('nguyễn văn a', '0987654321', '0987654321', '1995-05-20');

        $this->assertFalse($result->isNew);
        $this->assertTrue($result->candidate->is($existing));
        $this->assertSame([], $result->suspectedRoots);
    }

    public function test_exact_match_with_both_dob_missing_reuses_candidate(): void
    {
        $existing = $this->createCandidateWithPhone([
            'full_name' => 'Trần Thị B',
            'date_of_birth' => null,
        ], '0912345678');

        $result = $this->action->handle('Trần Thị B', '0912345678', '0912345678', null);

        $this->assertFalse($result->isNew);
        $this->assertTrue($result->candidate->is($existing));
    }

    public function test_multiple_exact_roots_creates_new_candidate_and_returns_multiple_exact_matches(): void
    {
        $rootA = $this->createCandidateWithPhone(['full_name' => 'Lê Văn C', 'date_of_birth' => null], '0900000001');
        $rootB = $this->createCandidateWithPhone(['full_name' => 'Lê Văn C', 'date_of_birth' => null], '0900000001');

        $result = $this->action->handle('Lê Văn C', '0900000001', '0900000001', null);

        $this->assertTrue($result->isNew);
        $this->assertCount(2, $result->suspectedRoots);
        $reasons = array_unique(array_map(fn ($r) => $r['reason']->value, $result->suspectedRoots));
        $this->assertSame([CandidateDuplicateReviewReason::MultipleExactMatches->value], array_values($reasons));
        $ids = array_map(fn ($r) => $r['candidate']->id, $result->suspectedRoots);
        $this->assertContains($rootA->id, $ids);
        $this->assertContains($rootB->id, $ids);
    }

    public function test_same_phone_different_name_is_suspected(): void
    {
        $existing = $this->createCandidateWithPhone(['full_name' => 'Phạm Văn D'], '0911111111');

        $result = $this->action->handle('Phạm Văn Khác', '0911111111', '0911111111', null);

        $this->assertTrue($result->isNew);
        $this->assertCount(1, $result->suspectedRoots);
        $this->assertTrue($result->suspectedRoots[0]['candidate']->is($existing));
        $this->assertSame(CandidateDuplicateReviewReason::SamePhoneDifferentName, $result->suspectedRoots[0]['reason']);
    }

    public function test_same_phone_missing_dob_is_suspected(): void
    {
        $existing = $this->createCandidateWithPhone(['full_name' => 'Hoàng Văn E', 'date_of_birth' => null], '0922222222');

        $result = $this->action->handle('Hoàng Văn E', '0922222222', '0922222222', '1990-01-01');

        $this->assertTrue($result->isNew);
        $this->assertCount(1, $result->suspectedRoots);
        $this->assertTrue($result->suspectedRoots[0]['candidate']->is($existing));
        $this->assertSame(CandidateDuplicateReviewReason::SamePhoneMissingDob, $result->suspectedRoots[0]['reason']);
    }

    public function test_same_identity_conflicting_dob_is_suspected(): void
    {
        $existing = $this->createCandidateWithPhone(['full_name' => 'Vũ Thị F', 'date_of_birth' => '1988-03-03'], '0933333333');

        $result = $this->action->handle('Vũ Thị F', '0933333333', '0933333333', '1999-09-09');

        $this->assertTrue($result->isNew);
        $this->assertCount(1, $result->suspectedRoots);
        $this->assertTrue($result->suspectedRoots[0]['candidate']->is($existing));
        $this->assertSame(CandidateDuplicateReviewReason::SameIdentityConflictingDob, $result->suspectedRoots[0]['reason']);
    }

    public function test_matching_contact_on_merged_candidate_resolves_to_root(): void
    {
        $root = Candidate::factory()->create(['full_name' => 'Đỗ Văn G', 'date_of_birth' => '2000-01-01']);
        $mergedSource = Candidate::factory()->create([
            'full_name' => 'Ten Cu Khac',
            'status' => 'merged',
            'merged_into_candidate_id' => $root->id,
        ]);
        CandidateContact::factory()->create([
            'candidate_id' => $mergedSource->id,
            'type' => 'phone',
            'value' => '0944444444',
            'normalized_value' => '0944444444',
            'is_primary' => true,
        ]);

        $result = $this->action->handle('Đỗ Văn G', '0944444444', '0944444444', '2000-01-01');

        $this->assertFalse($result->isNew);
        $this->assertTrue($result->candidate->is($root));
    }

    public function test_cyclic_merge_chain_is_excluded_and_falls_back_to_create_new(): void
    {
        $candidateA = Candidate::factory()->create(['full_name' => 'Vòng Lặp A']);
        $candidateB = Candidate::factory()->create([
            'full_name' => 'Vòng Lặp B',
            'status' => 'merged',
            'merged_into_candidate_id' => $candidateA->id,
        ]);
        $candidateA->forceFill(['status' => 'merged', 'merged_into_candidate_id' => $candidateB->id])->save();

        CandidateContact::factory()->create([
            'candidate_id' => $candidateA->id,
            'type' => 'phone',
            'value' => '0955555555',
            'normalized_value' => '0955555555',
            'is_primary' => true,
        ]);

        $result = $this->action->handle('Người Mới', '0955555555', '0955555555', null);

        $this->assertTrue($result->isNew);
        $this->assertSame([], $result->suspectedRoots);
        $this->assertNotSame($candidateA->id, $result->candidate->id);
        $this->assertNotSame($candidateB->id, $result->candidate->id);
    }

    public function test_two_merged_candidates_pointing_to_the_same_root_are_deduped(): void
    {
        $root = Candidate::factory()->create(['full_name' => 'Root Chung', 'date_of_birth' => null]);
        $mergedOne = Candidate::factory()->create(['status' => 'merged', 'merged_into_candidate_id' => $root->id]);
        $mergedTwo = Candidate::factory()->create(['status' => 'merged', 'merged_into_candidate_id' => $root->id]);

        CandidateContact::factory()->create([
            'candidate_id' => $mergedOne->id,
            'type' => 'phone',
            'value' => '0966666666',
            'normalized_value' => '0966666666',
            'is_primary' => true,
        ]);
        CandidateContact::factory()->create([
            'candidate_id' => $mergedTwo->id,
            'type' => 'phone',
            'value' => '0966666666',
            'normalized_value' => '0966666666',
            'is_primary' => false,
        ]);

        $result = $this->action->handle('Root Chung', '0966666666', '0966666666', null);

        $this->assertFalse($result->isNew);
        $this->assertTrue($result->candidate->is($root));
    }
}
