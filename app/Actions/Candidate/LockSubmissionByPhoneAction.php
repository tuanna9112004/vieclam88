<?php

namespace App\Actions\Candidate;

use App\Exceptions\SubmissionLockTimeoutException;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Submission Concurrency Contract (docs/CORE-FLOWS.md muc 3.1, ADR-061): named/advisory lock
 * cua MariaDB theo phone_normalized — serialize viec danh gia Duplicate Candidate Contract giua
 * 2 request dong thoi dung khac submission_token nhung cung phone. Khong bao gio dung so dien
 * thoai tho lam khoa hay ghi log.
 */
class LockSubmissionByPhoneAction
{
    public const LOCK_PREFIX = 'app_submit_phone:';

    public const DEFAULT_TIMEOUT_SECONDS = 5;

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function handle(string $phoneNormalized, Closure $callback, int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS): mixed
    {
        $lockKey = self::lockKey($phoneNormalized);

        $result = DB::selectOne('SELECT GET_LOCK(?, ?) AS acquired', [$lockKey, $timeoutSeconds]);

        if ((int) ($result->acquired ?? 0) !== 1) {
            throw new SubmissionLockTimeoutException;
        }

        try {
            return $callback();
        } finally {
            DB::statement('SELECT RELEASE_LOCK(?)', [$lockKey]);
        }
    }

    public static function lockKey(string $phoneNormalized): string
    {
        return self::LOCK_PREFIX.hash('sha256', $phoneNormalized);
    }
}
