<?php

namespace Tests\Feature\Foundation;

use App\Models\AdministrativeUnit;
use App\Models\Candidate;
use App\Models\CandidateContact;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_name_normalized_generated_on_create(): void
    {
        $candidate = Candidate::factory()->create(['full_name' => '  Nguyễn   Văn A!!  ']);

        $this->assertSame('nguyễn văn a', $candidate->full_name_normalized);
    }

    public function test_full_name_normalized_regenerated_on_update(): void
    {
        $candidate = Candidate::factory()->create(['full_name' => 'Tran Thi B']);

        $candidate->update(['full_name' => 'Lê  Thị,  C.']);

        $this->assertSame('lê thị c', $candidate->fresh()->full_name_normalized);
    }

    public function test_status_defaults_to_active(): void
    {
        $candidate = Candidate::factory()->create();

        $this->assertSame('active', $candidate->status);
    }

    public function test_deleting_referenced_administrative_unit_is_restricted(): void
    {
        $unit = AdministrativeUnit::factory()->create();
        Candidate::factory()->create(['current_administrative_unit_id' => $unit->id]);

        $this->expectException(QueryException::class);

        $unit->forceDelete();
    }

    public function test_deleting_merge_target_sets_merged_into_candidate_id_null(): void
    {
        $target = Candidate::factory()->create();
        $source = Candidate::factory()->create(['merged_into_candidate_id' => $target->id]);

        $target->forceDelete();

        $this->assertNull($source->fresh()->merged_into_candidate_id);
    }

    public function test_soft_delete(): void
    {
        $candidate = Candidate::factory()->create();

        $candidate->delete();

        $this->assertSoftDeleted('candidates', ['id' => $candidate->id]);
    }

    public function test_has_many_contacts(): void
    {
        $candidate = Candidate::factory()->create();
        $contact = CandidateContact::factory()->create(['candidate_id' => $candidate->id]);

        $this->assertTrue($candidate->contacts->first()->is($contact));
    }
}
