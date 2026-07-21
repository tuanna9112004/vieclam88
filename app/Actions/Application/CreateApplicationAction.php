<?php

namespace App\Actions\Application;

use App\Actions\Candidate\LockSubmissionByPhoneAction;
use App\Actions\Candidate\MatchOrCreateCandidateAction;
use App\Models\Application;
use App\Models\ApplicationBranchHistory;
use App\Models\ApplicationStatusHistory;
use App\Models\Candidate;
use App\Models\CandidateDuplicateReview;
use App\Models\Job;
use App\Support\PhoneNormalizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Luong 3 — Ung vien gui form ung tuyen (docs/CORE-FLOWS.md muc 3, 3.1, 6.2, 6.2.2), lap rap tu
 * cac Action da co: IssueSubmissionTokenAction (session, o Controller), LockSubmissionByPhoneAction
 * (ADR-061), MatchOrCreateCandidateAction (Duplicate Candidate Contract, muc 6.2/6.2.1).
 */
class CreateApplicationAction
{
    /** @var array<int, string> */
    private const STAGE_RANK = [
        'new', 'contacting', 'consulted', 'interview_scheduled', 'interviewed', 'waiting_start', 'started',
    ];

    public function __construct(
        private readonly LockSubmissionByPhoneAction $lock,
        private readonly MatchOrCreateCandidateAction $matchOrCreateCandidate,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data  Du lieu da validate tu StoreApplicationRequest.
     * @param  array{version: string, ip: ?string, user_agent: ?string}  $consent
     */
    public function handle(Job $job, array $data, string $submissionToken, array $consent): Application
    {
        $existing = Application::where('submission_token', $submissionToken)->first();
        if ($existing !== null) {
            return $existing;
        }

        $phoneNormalized = PhoneNormalizer::normalize($data['phone']);

        try {
            return $this->lock->handle($phoneNormalized, function () use ($job, $data, $submissionToken, $consent, $phoneNormalized) {
                return DB::transaction(function () use ($job, $data, $submissionToken, $consent, $phoneNormalized) {
                    return $this->createWithinLock($job, $data, $submissionToken, $consent, $phoneNormalized);
                });
            });
        } catch (QueryException $e) {
            if (! str_contains($e->getMessage(), 'applications_submission_token_unique')) {
                throw $e;
            }

            // Race: 2 request cung submission_token vuot qua kiem tra idempotency o tren truoc
            // khi request thang cuoc commit (ADR-041 buoc 7) — doc lai va tra ket qua da tao.
            return Application::where('submission_token', $submissionToken)->firstOrFail();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{version: string, ip: ?string, user_agent: ?string}  $consent
     */
    private function createWithinLock(Job $job, array $data, string $submissionToken, array $consent, string $phoneNormalized): Application
    {
        $existing = Application::where('submission_token', $submissionToken)->first();
        if ($existing !== null) {
            return $existing;
        }

        $matchResult = $this->matchOrCreateCandidate->handle(
            $data['full_name'],
            $data['phone'],
            $phoneNormalized,
            $data['date_of_birth'] ?? null,
        );
        $candidate = $matchResult->candidate;

        if ($matchResult->isNew) {
            $this->fillOptionalCandidateFields($candidate, $data);
        }

        $familyIds = $this->resolveMergedFamilyIds($candidate);
        $sameJobApplications = Application::whereIn('candidate_id', $familyIds)->where('job_id', $job->id)->get();

        if ($sameJobApplications->isNotEmpty()) {
            return $this->handleCaseC($sameJobApplications);
        }

        $application = $this->createApplication($job, $candidate, $data, $submissionToken, $consent);

        ApplicationStatusHistory::create([
            'application_id' => $application->id,
            'from_stage' => null,
            'to_stage' => 'new',
            'workflow_cycle' => 1,
            'changed_by' => null,
            'actor_type' => 'system',
        ]);

        ApplicationBranchHistory::create([
            'application_id' => $application->id,
            'from_branch_id' => null,
            'to_branch_id' => $job->owner_branch_id,
            'transferred_by' => null,
        ]);

        if ($matchResult->isNew && $matchResult->suspectedRoots !== []) {
            foreach ($matchResult->suspectedRoots as $suspected) {
                CandidateDuplicateReview::create([
                    'application_id' => $application->id,
                    'candidate_id' => $candidate->id,
                    'suspected_candidate_id' => $suspected['candidate']->id,
                    'reason_code' => $suspected['reason'],
                    'status' => 'pending',
                ]);
            }

            $application->update(['needs_duplicate_review' => true]);
        }

        return $application->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fillOptionalCandidateFields(Candidate $candidate, array $data): void
    {
        $optional = array_filter([
            'gender' => $data['gender'] ?? null,
            'current_administrative_unit_id' => $data['current_administrative_unit_id'] ?? null,
            'education_level' => $data['education_level'] ?? null,
            'experience_summary' => $data['experience_summary'] ?? null,
        ], fn ($value) => $value !== null);

        if ($optional !== []) {
            $candidate->fill($optional)->save();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{version: string, ip: ?string, user_agent: ?string}  $consent
     */
    private function createApplication(Job $job, Candidate $candidate, array $data, string $submissionToken, array $consent): Application
    {
        $now = now();
        $phoneNormalized = PhoneNormalizer::normalize($data['phone']);

        return Application::create([
            'public_id' => (string) Str::ulid(),
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'owner_branch_id' => $job->owner_branch_id,
            'stage' => 'new',
            'stage_changed_at' => $now,
            'workflow_cycle' => 1,
            'workflow_cycle_started_at' => $now,
            'submission_token' => $submissionToken,
            'needs_duplicate_review' => false,
            'submitted_full_name' => $data['full_name'],
            'submitted_phone' => $data['phone'],
            'submitted_phone_normalized' => $phoneNormalized,
            'submission_snapshot' => [
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'current_administrative_unit_id' => $data['current_administrative_unit_id'] ?? null,
                'education_level' => $data['education_level'] ?? null,
                'experience_summary' => $data['experience_summary'] ?? null,
            ],
            'job_snapshot' => [
                'title' => $job->title,
                'company_id' => $job->company_id,
                'employment_type' => $job->employment_type?->value,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'salary_period' => $job->salary_period,
            ],
            'consent_version' => $consent['version'],
            'consent_text_hash' => hash('sha256', $consent['version']),
            'consented_at' => $now,
            'consent_ip' => $consent['ip'],
            'consent_user_agent' => $consent['user_agent'],
        ]);
    }

    /**
     * Case C (docs/CORE-FLOWS.md muc 6.2.2): trong so cac Application da co cua family cho dung
     * Job, chon ban ghi canonical va chi cap nhat last_reapplied_at — khong tao moi, khong tu
     * mo lai.
     *
     * @param  Collection<int, Application>  $applications
     */
    private function handleCaseC(Collection $applications): Application
    {
        if ($applications->count() > 1) {
            Log::warning('Multiple applications found for same candidate family and job', [
                'application_ids' => $applications->pluck('id')->all(),
            ]);
        }

        $canonical = $this->pickCanonicalApplication($applications);
        $canonical->update(['last_reapplied_at' => now()]);

        return $canonical->fresh();
    }

    /**
     * @param  Collection<int, Application>  $applications
     */
    private function pickCanonicalApplication(Collection $applications): Application
    {
        return $applications->sort(function (Application $a, Application $b) {
            $aIsDuplicate = $a->close_reason === 'duplicate';
            $bIsDuplicate = $b->close_reason === 'duplicate';
            if ($aIsDuplicate !== $bIsDuplicate) {
                return $aIsDuplicate ? 1 : -1;
            }

            $aRank = array_search($a->stage, self::STAGE_RANK, true);
            $bRank = array_search($b->stage, self::STAGE_RANK, true);
            $aRank = $aRank === false ? -1 : $aRank;
            $bRank = $bRank === false ? -1 : $bRank;
            if ($aRank !== $bRank) {
                return $aRank > $bRank ? -1 : 1;
            }

            return $a->id <=> $b->id;
        })->first();
    }

    /**
     * Merged family cua 1 candidate (docs/CORE-FLOWS.md muc 6.3): candidate moi chi co family
     * gom chinh no; candidate da tung la dich cua (cac) merge co family gom ca cac nguon.
     *
     * @return array<int, int>
     */
    private function resolveMergedFamilyIds(Candidate $root): array
    {
        $familyIds = [$root->id];
        $frontier = [$root->id];

        while ($frontier !== []) {
            $children = Candidate::whereIn('merged_into_candidate_id', $frontier)->pluck('id')->all();
            $children = array_values(array_diff($children, $familyIds));
            if ($children === []) {
                break;
            }

            $familyIds = array_merge($familyIds, $children);
            $frontier = $children;
        }

        return $familyIds;
    }
}
