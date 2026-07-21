<?php

namespace Tests\Feature\Candidate;

use App\Actions\Candidate\LockSubmissionByPhoneAction;
use App\Exceptions\SubmissionLockTimeoutException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GET_LOCK cua MariaDB la khoa theo tung session/connection — de mo phong tranh chap that giua
 * 2 request dong thoi, test nay mo 1 connection Laravel thu hai (cung config DB, khac ten) thay
 * vi dung lai connection cua chinh test.
 */
class LockSubmissionByPhoneActionTest extends TestCase
{
    private const SECOND_CONNECTION = 'lock_test_secondary';

    protected function tearDown(): void
    {
        // Dong PDO cua connection thu hai (neu co mo) — MariaDB tu giai phong moi GET_LOCK cua
        // session do khi connection dong, khong can RELEASE_LOCK thu cong.
        DB::purge(self::SECOND_CONNECTION);

        parent::tearDown();
    }

    private function useSecondConnection(): \Illuminate\Database\Connection
    {
        $default = config('database.default');
        config(['database.connections.'.self::SECOND_CONNECTION => config("database.connections.$default")]);

        return DB::connection(self::SECOND_CONNECTION);
    }

    public function test_lock_key_is_deterministic_hash_and_never_contains_raw_phone(): void
    {
        $phone = '0987654321';

        $key = LockSubmissionByPhoneAction::lockKey($phone);

        $this->assertSame(LockSubmissionByPhoneAction::lockKey($phone), $key);
        $this->assertStringStartsWith('app_submit_phone:', $key);
        $this->assertStringNotContainsString($phone, $key);
    }

    public function test_different_phones_produce_different_lock_keys(): void
    {
        $this->assertNotSame(
            LockSubmissionByPhoneAction::lockKey('0987654321'),
            LockSubmissionByPhoneAction::lockKey('0911111111'),
        );
    }

    public function test_callback_runs_and_its_result_is_returned_when_lock_is_free(): void
    {
        $result = app(LockSubmissionByPhoneAction::class)->handle('0987654321', fn () => 'ok');

        $this->assertSame('ok', $result);
    }

    public function test_lock_is_released_after_success_so_it_can_be_acquired_again(): void
    {
        $phone = '0987654321';

        app(LockSubmissionByPhoneAction::class)->handle($phone, fn () => null);

        $second = app(LockSubmissionByPhoneAction::class)->handle($phone, fn () => 'ran-again', 1);

        $this->assertSame('ran-again', $second);
    }

    public function test_lock_is_released_even_when_callback_throws(): void
    {
        $phone = '0987654321';

        try {
            app(LockSubmissionByPhoneAction::class)->handle($phone, function () {
                throw new \RuntimeException('boom');
            });
            $this->fail('Expected exception was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $ranAfter = app(LockSubmissionByPhoneAction::class)->handle($phone, fn () => 'free-again', 1);
        $this->assertSame('free-again', $ranAfter);
    }

    public function test_times_out_with_friendly_exception_when_another_connection_holds_the_lock(): void
    {
        $phone = '0987654321';
        $lockKey = LockSubmissionByPhoneAction::lockKey($phone);

        $held = $this->useSecondConnection()->selectOne('SELECT GET_LOCK(?, 5) AS acquired', [$lockKey]);
        $this->assertSame(1, (int) $held->acquired);

        $this->expectException(SubmissionLockTimeoutException::class);

        app(LockSubmissionByPhoneAction::class)->handle($phone, fn () => 'should-not-run', 1);
    }
}
