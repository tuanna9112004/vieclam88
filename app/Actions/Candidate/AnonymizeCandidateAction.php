<?php

namespace App\Actions\Candidate;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\CandidateContact;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * docs/CORE-FLOWS.md mục 7.2 & 7.3, ADR-056 — Anonymize Candidate theo PII Contract.
 * Chỉ Admin mới có quyền thực hiện. Không thể hoàn tác (irreversible).
 */
class AnonymizeCandidateAction
{
    public function handle(Candidate $candidate, User $actor): Candidate
    {
        if ($candidate->trashed()) {
            throw ValidationException::withMessages([
                'candidate' => 'Ứng viên đã bị xóa, không thể ẩn danh.',
            ]);
        }

        if ($candidate->status === 'anonymized') {
            throw ValidationException::withMessages([
                'candidate' => 'Ứng viên đã được ẩn danh trước đó.',
            ]);
        }

        return DB::transaction(function () use ($candidate, $actor) {
            /** @var Candidate $lockedCandidate */
            $lockedCandidate = Candidate::whereKey($candidate->id)->lockForUpdate()->firstOrFail();

            if ($lockedCandidate->status === 'anonymized') {
                throw ValidationException::withMessages([
                    'candidate' => 'Ứng viên đã được ẩn danh trước đó.',
                ]);
            }

            // 1. Anonymize Candidate record
            $lockedCandidate->update([
                'full_name' => '[ĐÃ ẨN DANH]',
                'full_name_normalized' => '[DA AN DANH]',
                'date_of_birth' => null,
                'current_administrative_unit_id' => null,
                'address_detail' => null,
                'status' => 'anonymized',
                'anonymized_at' => now(),
                'anonymized_by' => $actor->id,
            ]);

            // 2. Anonymize Candidate Contacts
            $contacts = CandidateContact::where('candidate_id', $lockedCandidate->id)->get();
            foreach ($contacts as $contact) {
                $maskedValue = '0000000000-'.$contact->id;
                $contact->update([
                    'value' => $maskedValue,
                    'normalized_value' => $maskedValue,
                    'is_active' => false,
                ]);
            }

            // 3. Anonymize Applications (6 PII fields per ADR-056 & section 7.2.1)
            $applications = Application::where('candidate_id', $lockedCandidate->id)->lockForUpdate()->get();

            foreach ($applications as $app) {
                $snapshot = $app->submission_snapshot;
                if (is_array($snapshot)) {
                    if (isset($snapshot['full_name'])) {
                        $snapshot['full_name'] = '[ĐÃ ẨN DANH]';
                    }
                    if (isset($snapshot['phone'])) {
                        $snapshot['phone'] = '0000000000';
                    }
                    if (isset($snapshot['phone_normalized'])) {
                        $snapshot['phone_normalized'] = '0000000000';
                    }
                    if (isset($snapshot['date_of_birth'])) {
                        $snapshot['date_of_birth'] = null;
                    }
                    if (isset($snapshot['address_detail'])) {
                        $snapshot['address_detail'] = null;
                    }
                }

                $app->update([
                    'submitted_full_name' => '[ĐÃ ẨN DANH]',
                    'submitted_phone' => '0000000000',
                    'submitted_phone_normalized' => '0000000000',
                    'submission_snapshot' => $snapshot,
                    'consent_ip' => null,
                    'consent_user_agent' => null,
                ]);
            }

            return $lockedCandidate;
        });
    }
}
