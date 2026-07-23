<?php

namespace Tests\Feature\Hr\Candidate;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\CandidateContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateAnonymizeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected(): void
    {
        $candidate = Candidate::factory()->create(['full_name' => 'Nguyen Van A']);

        $this->post(route('hr.candidates.anonymize', $candidate), [
            'confirm_name' => 'Nguyen Van A',
        ])->assertRedirect(route('hr.login'));
    }

    public function test_staff_is_forbidden(): void
    {
        $staff = User::factory()->create();
        $candidate = Candidate::factory()->create(['full_name' => 'Nguyen Van A']);

        $this->actingAs($staff)->post(route('hr.candidates.anonymize', $candidate), [
            'confirm_name' => 'Nguyen Van A',
        ])->assertForbidden();

        $this->assertSame('active', $candidate->fresh()->status);
    }

    public function test_confirmation_name_must_match_exactly(): void
    {
        $admin = User::factory()->admin()->create();
        $candidate = Candidate::factory()->create(['full_name' => 'Nguyen Van A']);

        $this->actingAs($admin)->post(route('hr.candidates.anonymize', $candidate), [
            'confirm_name' => 'Ten Sai',
        ])->assertSessionHasErrors('confirm_name');

        $this->assertSame('active', $candidate->fresh()->status);
    }

    public function test_admin_can_anonymize_with_correct_confirmation(): void
    {
        $admin = User::factory()->admin()->create();
        $candidate = Candidate::factory()->create([
            'full_name' => 'Nguyen Van A',
            'date_of_birth' => '1995-01-01',
            'address_detail' => '123 Duong ABC',
        ]);
        CandidateContact::factory()->create([
            'candidate_id' => $candidate->id, 'type' => 'phone', 'value' => '0987654321',
        ]);
        $application = Application::factory()->create([
            'candidate_id' => $candidate->id,
            'submitted_full_name' => 'Nguyen Van A',
            'submitted_phone' => '0987654321',
            'consent_ip' => '127.0.0.1',
            'consent_user_agent' => 'Mozilla/5.0',
        ]);

        $this->actingAs($admin)->post(route('hr.candidates.anonymize', $candidate), [
            'confirm_name' => 'Nguyen Van A',
        ])->assertRedirect(route('hr.candidates.show', $candidate));

        $fresh = $candidate->fresh();
        $this->assertSame('anonymized', $fresh->status);
        $this->assertSame('[ĐÃ ẨN DANH]', $fresh->full_name);
        $this->assertNull($fresh->date_of_birth);
        $this->assertNull($fresh->address_detail);
        $this->assertSame($admin->id, $fresh->anonymized_by);
        $this->assertNotNull($fresh->anonymized_at);

        $freshContact = CandidateContact::where('candidate_id', $candidate->id)->first();
        $this->assertNotSame('0987654321', $freshContact->value);
        $this->assertFalse((bool) $freshContact->is_active);

        $freshApp = $application->fresh();
        $this->assertSame('[ĐÃ ẨN DANH]', $freshApp->submitted_full_name);
        $this->assertSame('0000000000', $freshApp->submitted_phone);
        $this->assertNull($freshApp->consent_ip);
        $this->assertNull($freshApp->consent_user_agent);
    }

    public function test_cannot_anonymize_twice(): void
    {
        $admin = User::factory()->admin()->create();
        $candidate = Candidate::factory()->create([
            'full_name' => '[ĐÃ ẨN DANH]', 'status' => 'anonymized', 'anonymized_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('hr.candidates.anonymize', $candidate), [
            'confirm_name' => '[ĐÃ ẨN DANH]',
        ])->assertSessionHasErrors('candidate');
    }

    public function test_view_no_longer_shows_original_pii_after_anonymize(): void
    {
        $admin = User::factory()->admin()->create();
        $candidate = Candidate::factory()->create(['full_name' => 'Nguyen Van Bi Mat']);
        Application::factory()->create([
            'candidate_id' => $candidate->id,
            'submitted_full_name' => 'Nguyen Van Bi Mat',
            'submitted_phone' => '0987654321',
        ]);

        $this->actingAs($admin)->post(route('hr.candidates.anonymize', $candidate), [
            'confirm_name' => 'Nguyen Van Bi Mat',
        ]);

        $response = $this->actingAs($admin)->get(route('hr.candidates.show', $candidate));

        $response->assertOk();
        $response->assertDontSee('Nguyen Van Bi Mat');
        $response->assertDontSee('0987654321');
    }
}
