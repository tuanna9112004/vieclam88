<?php

namespace Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\TestDatabaseGuard;

/**
 * Unit test thuần cho logic guard — không boot Laravel, không mở kết nối database
 * thật (kể cả database test). Chỉ gọi trực tiếp TestDatabaseGuard::assertSafe()
 * với giá trị giả lập.
 */
class TestDatabaseGuardTest extends TestCase
{
    public function test_allows_testing_environment_with_isolated_database(): void
    {
        $this->expectNotToPerformAssertions();

        TestDatabaseGuard::assertSafe('testing', 'vieclam88_test');
    }

    public function test_blocks_when_environment_is_not_testing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('APP_ENV=testing');

        TestDatabaseGuard::assertSafe('local', 'vieclam88_test');
    }

    public function test_blocks_when_database_matches_development_database(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("'".TestDatabaseGuard::DEV_DATABASE."'");

        TestDatabaseGuard::assertSafe('testing', TestDatabaseGuard::DEV_DATABASE);
    }

    public function test_blocks_when_database_is_null(): void
    {
        $this->expectException(RuntimeException::class);

        TestDatabaseGuard::assertSafe('testing', null);
    }

    public function test_blocks_when_database_is_empty_string(): void
    {
        $this->expectException(RuntimeException::class);

        TestDatabaseGuard::assertSafe('testing', '');
    }

    public function test_environment_check_runs_before_database_check_fail_closed(): void
    {
        // Sai cả environment lẫn database — vẫn phải chặn (fail closed), không
        // được "may mắn" pass vì kiểm tra sai thứ tự.
        $this->expectException(RuntimeException::class);

        TestDatabaseGuard::assertSafe('production', TestDatabaseGuard::DEV_DATABASE);
    }
}
