<?php

namespace App\Actions\Application;

use Illuminate\Contracts\Session\Session;

/**
 * Submission Token Lifecycle (docs/CORE-FLOWS.md muc 3, ADR-041): sinh token gan voi dung 1
 * Job, luu vao session duoi dang mang nhieu token (ho tro mo nhieu Job o nhieu tab cung luc,
 * khong ghi de lan nhau).
 */
class IssueSubmissionTokenAction
{
    public const SESSION_KEY = 'submission_tokens';

    public function handle(Session $session, int $jobId): string
    {
        $token = bin2hex(random_bytes(32));

        $tokens = $session->get(self::SESSION_KEY, []);
        $tokens[$token] = [
            'job_id' => $jobId,
            'issued_at' => now()->toIso8601String(),
        ];
        $session->put(self::SESSION_KEY, $tokens);

        return $token;
    }
}
