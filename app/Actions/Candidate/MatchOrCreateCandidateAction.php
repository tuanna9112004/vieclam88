<?php

namespace App\Actions\Candidate;

use App\Enums\CandidateDuplicateReviewReason;
use App\Models\Candidate;
use App\Models\CandidateContact;
use App\Support\VietnameseNormalizer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Duplicate Candidate Contract + Merged-root resolution (docs/CORE-FLOWS.md muc 6.2/6.2.1,
 * ADR-040, ADR-063). Gia dinh da chay sau khi caller giu duoc named lock theo phone_normalized
 * (ADR-061, muc 3.1) — Action nay khong tu lock.
 */
class MatchOrCreateCandidateAction
{
    private const MAX_MERGE_CHAIN_DEPTH = 20;

    /**
     * @param  string|null  $dateOfBirth  Dinh dang 'Y-m-d' hoac null.
     */
    public function handle(string $fullName, string $phoneRaw, string $phoneNormalized, ?string $dateOfBirth): CandidateMatchResult
    {
        $normalizedFullName = VietnameseNormalizer::normalize($fullName);

        $roots = $this->resolveMatchingRoots($phoneNormalized);

        $exactRoots = [];
        $suspectedRoots = [];

        foreach ($roots as $root) {
            $this->classify($root, $normalizedFullName, $dateOfBirth, $exactRoots, $suspectedRoots);
        }

        if (count($exactRoots) === 1) {
            return new CandidateMatchResult($exactRoots[0], false, []);
        }

        if (count($exactRoots) > 1) {
            $suspectedRoots = array_map(
                fn (Candidate $root) => ['candidate' => $root, 'reason' => CandidateDuplicateReviewReason::MultipleExactMatches],
                $exactRoots
            );
        }

        $candidate = $this->createCandidate($fullName, $dateOfBirth);
        $this->createPhoneContact($candidate, $phoneRaw, $phoneNormalized);

        return new CandidateMatchResult($candidate, true, $suspectedRoots);
    }

    /**
     * @return array<int, Candidate> Danh sach root, dedupe theo root id.
     */
    private function resolveMatchingRoots(string $phoneNormalized): array
    {
        $matchedCandidateIds = CandidateContact::query()
            ->where('type', 'phone')
            ->where('normalized_value', $phoneNormalized)
            ->pluck('candidate_id')
            ->unique();

        $roots = [];

        foreach ($matchedCandidateIds as $candidateId) {
            $candidate = Candidate::find($candidateId);
            if ($candidate === null) {
                continue;
            }

            $root = $this->resolveRoot($candidate);
            if ($root !== null) {
                $roots[$root->id] = $root;
            }
        }

        return array_values($roots);
    }

    /**
     * @param  array<int, Candidate>  $exactRoots
     * @param  array<int, array{candidate: Candidate, reason: CandidateDuplicateReviewReason}>  $suspectedRoots
     */
    private function classify(Candidate $root, string $normalizedFullName, ?string $dateOfBirth, array &$exactRoots, array &$suspectedRoots): void
    {
        if ($root->full_name_normalized !== $normalizedFullName) {
            $suspectedRoots[] = ['candidate' => $root, 'reason' => CandidateDuplicateReviewReason::SamePhoneDifferentName];

            return;
        }

        $rootDob = $root->date_of_birth?->toDateString();

        if ($dateOfBirth === null && $rootDob === null) {
            $exactRoots[] = $root;

            return;
        }

        if ($dateOfBirth !== null && $rootDob !== null) {
            if ($dateOfBirth === $rootDob) {
                $exactRoots[] = $root;
            } else {
                $suspectedRoots[] = ['candidate' => $root, 'reason' => CandidateDuplicateReviewReason::SameIdentityConflictingDob];
            }

            return;
        }

        $suspectedRoots[] = ['candidate' => $root, 'reason' => CandidateDuplicateReviewReason::SamePhoneMissingDob];
    }

    /**
     * Di theo merged_into_candidate_id toi root (chua merged), toi da 20 buoc. Phat hien
     * vong lap/chain lỗi (tro toi candidate da di qua hoac candidate khong ton tai) thi ghi log
     * ky thuat va tra ve null — candidate do bi loai khoi tap so khop, khong chan ung vien nop
     * don vi loi du lieu noi bo (docs/CORE-FLOWS.md muc 6.2.1, ADR-075).
     */
    private function resolveRoot(Candidate $candidate): ?Candidate
    {
        $visited = [];
        $current = $candidate;

        for ($step = 0; $step < self::MAX_MERGE_CHAIN_DEPTH; $step++) {
            if (isset($visited[$current->id])) {
                Log::error('Candidate merge chain cycle detected', ['candidate_id' => $current->id]);

                return null;
            }
            $visited[$current->id] = true;

            if ($current->merged_into_candidate_id === null) {
                return $current;
            }

            $next = Candidate::find($current->merged_into_candidate_id);
            if ($next === null) {
                Log::error('Candidate merge chain points to a missing candidate', ['candidate_id' => $current->id]);

                return null;
            }

            $current = $next;
        }

        Log::error('Candidate merge chain exceeded max depth', ['candidate_id' => $candidate->id]);

        return null;
    }

    private function createCandidate(string $fullName, ?string $dateOfBirth): Candidate
    {
        return Candidate::create([
            'public_id' => (string) Str::ulid(),
            'full_name' => $fullName,
            'date_of_birth' => $dateOfBirth,
            'status' => 'active',
        ]);
    }

    private function createPhoneContact(Candidate $candidate, string $phoneRaw, string $phoneNormalized): CandidateContact
    {
        return CandidateContact::create([
            'candidate_id' => $candidate->id,
            'type' => 'phone',
            'value' => $phoneRaw,
            'normalized_value' => $phoneNormalized,
            'is_primary' => true,
            'is_verified' => false,
            'is_active' => true,
        ]);
    }
}
