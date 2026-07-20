<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chứng minh bằng lifecycle THẬT của Laravel (không phải chỉ đọc code bằng mắt) rằng
 * createApplication() — nơi TestDatabaseGuard chạy — luôn được gọi TRƯỚC
 * refreshDatabase() (nơi RefreshDatabase sẽ chạy migrate:fresh). Ghi đè hẳn
 * refreshDatabase() để KHÔNG bao giờ gọi migrate:fresh/chạm bất kỳ database nào
 * (kể cả database test) — chỉ ghi lại thứ tự gọi thực tế bằng static log.
 */
class GuardOrderingTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    public static array $callOrder = [];

    public function createApplication()
    {
        $app = parent::createApplication();

        self::$callOrder[] = 'createApplication (guard đã chạy bên trong parent::createApplication)';

        return $app;
    }

    /**
     * Ghi đè hoàn toàn — KHÔNG gọi parent::refreshDatabase(), nên migrate:fresh
     * không bao giờ thực sự chạy trong test này.
     */
    public function refreshDatabase(): void
    {
        self::$callOrder[] = 'refreshDatabase (KHÔNG gọi thật — chỉ probe thứ tự)';
    }

    public function test_create_application_runs_before_refresh_database_in_real_lifecycle(): void
    {
        $this->assertSame(
            [
                'createApplication (guard đã chạy bên trong parent::createApplication)',
                'refreshDatabase (KHÔNG gọi thật — chỉ probe thứ tự)',
            ],
            self::$callOrder,
            'createApplication() (chứa guard) phải chạy trước refreshDatabase() (nơi migrate:fresh sẽ chạy) trong lifecycle thật của Illuminate\Foundation\Testing\TestCase.'
        );
    }
}
