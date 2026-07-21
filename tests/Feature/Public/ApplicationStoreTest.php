<?php

namespace Tests\Feature\Public;

use App\Actions\Application\IssueSubmissionTokenAction;
use App\Actions\Candidate\LockSubmissionByPhoneAction;
use App\Enums\CandidateDuplicateReviewStatus;
use App\Models\AdministrativeUnit;
use App\Models\Application;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\CandidateContact;
use App\Models\CandidateDuplicateReview;
use App\Models\CompanyLocation;
use App\Models\Job;
use App\Models\JobLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApplicationStoreTest extends TestCase
{
    use RefreshDatabase;

    private const SECOND_CONNECTION = 'lock_test_secondary';

    protected function tearDown(): void
    {
        // Cung ly do voi LockSubmissionByPhoneActionTest: dong PDO thu 2 de MariaDB tu giai
        // phong GET_LOCK cua session do.
        DB::purge(self::SECOND_CONNECTION);

        parent::tearDown();
    }

    private AdministrativeUnit $unit;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = AdministrativeUnit::factory()->create();
        $this->branch = Branch::factory()->create([
            'status' => 'active',
            'administrative_unit_id' => $this->unit->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createJob(array $overrides = []): Job
    {
        $job = Job::factory()->create(array_merge([
            'status' => 'published',
            'owner_branch_id' => $this->branch->id,
        ], $overrides));

        $companyLocation = CompanyLocation::factory()->create([
            'company_id' => $job->company_id,
            'administrative_unit_id' => $this->unit->id,
        ]);

        JobLocation::factory()->create([
            'job_id' => $job->id,
            'company_location_id' => $companyLocation->id,
            'is_primary' => true,
        ]);

        return $job->fresh();
    }

    private function issueTokenFor(Job $job): string
    {
        $this->get(route('jobs.show', $job->slug))->assertOk();

        $tokens = session(IssueSubmissionTokenAction::SESSION_KEY, []);

        foreach (array_reverse($tokens, true) as $token => $meta) {
            if ($meta['job_id'] === $job->id) {
                return $token;
            }
        }

        $this->fail('No submission token issued for job.');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(string $token, array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'date_of_birth' => '1995-05-20',
            'consent' => '1',
            'submission_token' => $token,
            'website' => '',
        ], $overrides);
    }

    public function test_apply_form_renders_optional_fields(): void
    {
        $job = $this->createJob();

        $response = $this->get(route('jobs.show', $job->slug))->assertOk();

        $response->assertSee('Thông tin bổ sung');
        foreach (['Giới tính', 'Nơi ở hiện tại', 'Học vấn', 'Kinh nghiệm làm việc'] as $label) {
            $response->assertSee($label);
        }
    }

    public function test_valid_submission_creates_application_with_status_and_branch_history(): void
    {
        $job = $this->createJob();
        $token = $this->issueTokenFor($job);

        $response = $this->post(route('applications.store', $job->slug), $this->validPayload($token));

        $response->assertRedirect(route('jobs.show', $job->slug));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('applications', [
            'job_id' => $job->id,
            'owner_branch_id' => $job->owner_branch_id,
            'stage' => 'new',
            'workflow_cycle' => 1,
            'submission_token' => $token,
        ]);

        $application = Application::where('submission_token', $token)->firstOrFail();

        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id,
            'from_stage' => null,
            'to_stage' => 'new',
            'workflow_cycle' => 1,
        ]);

        $this->assertDatabaseHas('application_branch_histories', [
            'application_id' => $application->id,
            'from_branch_id' => null,
            'to_branch_id' => $job->owner_branch_id,
        ]);
    }

    public function test_optional_fields_can_be_left_empty(): void
    {
        $job = $this->createJob();
        $token = $this->issueTokenFor($job);

        $this->post(route('applications.store', $job->slug), $this->validPayload($token))->assertRedirect();

        $candidate = Candidate::firstOrFail();
        $this->assertNull($candidate->gender);
        $this->assertNull($candidate->current_administrative_unit_id);
        $this->assertNull($candidate->education_level);
        $this->assertNull($candidate->experience_summary);
        $this->assertSame(1, CandidateContact::where('candidate_id', $candidate->id)->count());
    }

    public function test_optional_fields_are_persisted_when_provided(): void
    {
        $job = $this->createJob();
        $token = $this->issueTokenFor($job);
        $residenceUnit = AdministrativeUnit::factory()->create(['is_active' => true]);

        $this->post(route('applications.store', $job->slug), $this->validPayload($token, [
            'gender' => 'female',
            'current_administrative_unit_id' => $residenceUnit->id,
            'education_level' => '12/12',
            'experience_summary' => '2 nam lam cong nhan',
        ]))->assertRedirect();

        $candidate = Candidate::firstOrFail();
        $this->assertSame('female', $candidate->gender);
        $this->assertSame($residenceUnit->id, $candidate->current_administrative_unit_id);
        $this->assertSame('12/12', $candidate->education_level);
        $this->assertSame('2 nam lam cong nhan', $candidate->experience_summary);
    }

    public function test_inactive_administrative_unit_for_residence_is_rejected(): void
    {
        $job = $this->createJob();
        $token = $this->issueTokenFor($job);
        $inactiveUnit = AdministrativeUnit::factory()->create(['is_active' => false]);

        $response = $this->post(route('applications.store', $job->slug), $this->validPayload($token, [
            'current_administrative_unit_id' => $inactiveUnit->id,
        ]));

        $response->assertSessionHasErrors('current_administrative_unit_id');
        $this->assertSame(0, Application::count());
    }

    public function test_missing_or_mismatched_submission_token_is_rejected_without_creating_records(): void
    {
        $job = $this->createJob();
        $otherJob = $this->createJob();
        $tokenForOtherJob = $this->issueTokenFor($otherJob);

        $response = $this->post(route('applications.store', $job->slug), $this->validPayload($tokenForOtherJob));

        $response->assertSessionHasErrors('submission_token');
        $this->assertSame(0, Application::count());
        $this->assertSame(0, Candidate::count());
    }

    public function test_honeypot_filled_is_rejected(): void
    {
        $job = $this->createJob();
        $token = $this->issueTokenFor($job);

        $response = $this->post(route('applications.store', $job->slug), $this->validPayload($token, ['website' => 'http://spam.example']));

        $response->assertSessionHasErrors('website');
        $this->assertSame(0, Application::count());
    }

    public function test_missing_consent_is_rejected(): void
    {
        $job = $this->createJob();
        $token = $this->issueTokenFor($job);

        $response = $this->post(route('applications.store', $job->slug), $this->validPayload($token, ['consent' => null]));

        $response->assertSessionHasErrors('consent');
        $this->assertSame(0, Application::count());
    }

    public function test_duplicate_submission_with_same_token_does_not_create_a_second_application(): void
    {
        $job = $this->createJob();
        $token = $this->issueTokenFor($job);
        $payload = $this->validPayload($token);

        $this->post(route('applications.store', $job->slug), $payload)->assertRedirect();
        $this->post(route('applications.store', $job->slug), $payload)->assertRedirect();

        $this->assertSame(1, Application::where('submission_token', $token)->count());
        $this->assertSame(1, Candidate::count());
    }

    public function test_reapplying_to_same_job_updates_last_reapplied_at_instead_of_creating_new_application(): void
    {
        $job = $this->createJob();

        $firstToken = $this->issueTokenFor($job);
        $this->post(route('applications.store', $job->slug), $this->validPayload($firstToken))->assertRedirect();

        $original = Application::firstOrFail();
        $this->assertNull($original->last_reapplied_at);

        $secondToken = $this->issueTokenFor($job);
        $this->post(route('applications.store', $job->slug), $this->validPayload($secondToken))->assertRedirect();

        $this->assertSame(1, Application::count());
        $this->assertSame(1, Candidate::count());
        $this->assertNotNull($original->fresh()->last_reapplied_at);
    }

    public function test_suspected_duplicate_candidate_creates_pending_review_and_flags_application(): void
    {
        $existingCandidate = Candidate::factory()->create(['full_name' => 'Người Đã Có', 'date_of_birth' => null]);
        CandidateContact::factory()->create([
            'candidate_id' => $existingCandidate->id,
            'type' => 'phone',
            'value' => '0987654321',
            'normalized_value' => '0987654321',
            'is_primary' => true,
        ]);

        $job = $this->createJob();
        $token = $this->issueTokenFor($job);

        $this->post(route('applications.store', $job->slug), $this->validPayload($token, [
            'full_name' => 'Tên Hoàn Toàn Khác',
        ]))->assertRedirect();

        $application = Application::firstOrFail();
        $this->assertTrue((bool) $application->needs_duplicate_review);

        $review = CandidateDuplicateReview::where('application_id', $application->id)->firstOrFail();
        $this->assertSame($existingCandidate->id, $review->suspected_candidate_id);
        $this->assertSame(CandidateDuplicateReviewStatus::Pending, $review->status);
        $this->assertNotSame($existingCandidate->id, $review->candidate_id);
    }

    public function test_requests_beyond_rate_limit_are_throttled(): void
    {
        $job = $this->createJob();

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('applications.store', $job->slug), []);
        }

        $response = $this->post(route('applications.store', $job->slug), []);

        $response->assertStatus(429);
    }

    public function test_submitting_to_a_closed_job_is_rejected_even_with_valid_token(): void
    {
        $job = $this->createJob();
        $token = $this->issueTokenFor($job);
        $job->update(['status' => 'closed']);

        $response = $this->post(route('applications.store', $job->slug), $this->validPayload($token));

        $response->assertRedirect(route('jobs.show', $job->slug));
        $response->assertSessionHas('error');
        $this->assertSame(0, Application::count());
    }

    public function test_lock_timeout_redirects_with_friendly_error_instead_of_500(): void
    {
        $job = $this->createJob();
        $token = $this->issueTokenFor($job);
        $payload = $this->validPayload($token);

        $lockKey = LockSubmissionByPhoneAction::lockKey('0987654321');
        $default = config('database.default');
        config(['database.connections.'.self::SECOND_CONNECTION => config("database.connections.$default")]);
        $held = DB::connection(self::SECOND_CONNECTION)->selectOne('SELECT GET_LOCK(?, 5) AS acquired', [$lockKey]);
        $this->assertSame(1, (int) $held->acquired);

        $response = $this->post(route('applications.store', $job->slug), $payload);

        $response->assertRedirect(route('jobs.show', $job->slug));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('thử lại', (string) session('error'));
        $this->assertSame(0, Application::count());
        $this->assertSame(0, Candidate::count());
    }

    /**
     * Ket hop ca 2 nua cua Submission Concurrency Contract (docs/CORE-FLOWS.md muc 3.1,
     * ADR-061) trong 1 kich ban: token khac nhau + cung identity (ten/sdt/ngay sinh).
     * PHP tren Windows khong co pcntl nen khong the chay 2 request that song song trong 1
     * tien trinh — thay vao do, dung 1 connection PDO that su thu hai de gia lap "request A
     * dang giu lock giua chung" (dung ky thuat nhu LockSubmissionByPhoneActionTest), roi kiem
     * tra request B (token khac) trong luc bi chan va sau khi lock duoc nha:
     * 1) trong luc bi chan: khong duoc tao them Candidate/Application nao, loi phai than thien;
     * 2) sau khi lock nha: request B phai duoc resolve ve dung Candidate/Application da co
     *    (Case C), khong bao gio co 2 Candidate hay 2 Application cho cung 1 nguoi.
     */
    public function test_two_different_tokens_same_identity_are_serialized_by_the_phone_lock_and_deduplicated(): void
    {
        $job = $this->createJob();

        $firstToken = $this->issueTokenFor($job);
        $this->post(route('applications.store', $job->slug), $this->validPayload($firstToken))->assertRedirect();
        $this->assertSame(1, Candidate::count());
        $this->assertSame(1, Application::count());

        $lockKey = LockSubmissionByPhoneAction::lockKey('0987654321');
        $default = config('database.default');
        config(['database.connections.'.self::SECOND_CONNECTION => config("database.connections.$default")]);
        $held = DB::connection(self::SECOND_CONNECTION)->selectOne('SELECT GET_LOCK(?, 5) AS acquired', [$lockKey]);
        $this->assertSame(1, (int) $held->acquired);

        $secondToken = $this->issueTokenFor($job);
        $blockedResponse = $this->post(route('applications.store', $job->slug), $this->validPayload($secondToken));

        $blockedResponse->assertSessionHas('error');
        $this->assertSame(1, Candidate::count(), 'Trong luc lock bi giu, khong duoc tao them Candidate.');
        $this->assertSame(1, Application::count(), 'Trong luc lock bi giu, khong duoc tao them Application.');

        DB::connection(self::SECOND_CONNECTION)->statement('SELECT RELEASE_LOCK(?)', [$lockKey]);

        $thirdToken = $this->issueTokenFor($job);
        $this->post(route('applications.store', $job->slug), $this->validPayload($thirdToken))->assertRedirect();

        $this->assertSame(1, Candidate::count(), 'Hai token khac nhau cung identity khong duoc tao 2 Candidate.');
        $this->assertSame(1, Application::count(), 'Hai token khac nhau cung identity khong duoc tao 2 Application.');
    }
}
