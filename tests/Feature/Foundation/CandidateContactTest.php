<?php

namespace Tests\Feature\Foundation;

use App\Models\Candidate;
use App\Models\CandidateContact;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_type_normalized_value_must_be_unique(): void
    {
        $candidate = Candidate::factory()->create();
        CandidateContact::factory()->create([
            'candidate_id' => $candidate->id,
            'type' => 'phone',
            'normalized_value' => '0900000000',
        ]);

        $this->expectException(QueryException::class);

        CandidateContact::factory()->create([
            'candidate_id' => $candidate->id,
            'type' => 'phone',
            'normalized_value' => '0900000000',
        ]);
    }

    public function test_only_one_primary_contact_allowed_per_candidate_and_type(): void
    {
        $candidate = Candidate::factory()->create();
        CandidateContact::factory()->create([
            'candidate_id' => $candidate->id,
            'type' => 'phone',
            'is_primary' => true,
        ]);

        $this->expectException(QueryException::class);

        CandidateContact::factory()->create([
            'candidate_id' => $candidate->id,
            'type' => 'phone',
            'is_primary' => true,
        ]);
    }

    public function test_multiple_non_primary_contacts_allowed_per_candidate_and_type(): void
    {
        $candidate = Candidate::factory()->create();
        CandidateContact::factory()->create(['candidate_id' => $candidate->id, 'type' => 'phone', 'is_primary' => false]);
        CandidateContact::factory()->create(['candidate_id' => $candidate->id, 'type' => 'phone', 'is_primary' => false]);

        $this->assertSame(2, CandidateContact::where('candidate_id', $candidate->id)->count());
    }

    public function test_primary_contacts_of_different_types_allowed_for_same_candidate(): void
    {
        $candidate = Candidate::factory()->create();
        CandidateContact::factory()->create([
            'candidate_id' => $candidate->id,
            'type' => 'phone',
            'is_primary' => true,
        ]);
        $email = CandidateContact::factory()->create([
            'candidate_id' => $candidate->id,
            'type' => 'email',
            'normalized_value' => 'a@example.com',
            'is_primary' => true,
        ]);

        $this->assertTrue($email->is_primary);
    }

    public function test_deleting_candidate_cascades_candidate_contacts(): void
    {
        $candidate = Candidate::factory()->create();
        $contact = CandidateContact::factory()->create(['candidate_id' => $candidate->id]);

        $candidate->forceDelete();

        $this->assertDatabaseMissing('candidate_contacts', ['id' => $contact->id]);
    }

    public function test_belongs_to_candidate(): void
    {
        $candidate = Candidate::factory()->create();
        $contact = CandidateContact::factory()->create(['candidate_id' => $candidate->id]);

        $this->assertTrue($contact->candidate->is($candidate));
    }
}
