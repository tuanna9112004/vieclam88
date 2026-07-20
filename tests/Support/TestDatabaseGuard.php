<?php

namespace Tests\Support;

use RuntimeException;

/**
 * Logic thuần (không phụ thuộc Laravel Application/DB) để có thể unit-test guard
 * mà không cần boot framework hay chạm bất kỳ database nào. Được gọi từ
 * Tests\TestCase::createApplication() — chạy trước setUpTraits()/RefreshDatabase
 * trong lifecycle gốc của Illuminate\Foundation\Testing\TestCase.
 */
class TestDatabaseGuard
{
    public const DEV_DATABASE = 'vieclam88';

    /**
     * Fail closed: chỉ coi là an toàn khi APP_ENV=testing VÀ tên database khác
     * database development. Bất kỳ giá trị thiếu/rỗng/không xác định nào đều chặn.
     *
     * @throws RuntimeException khi cấu hình không an toàn cho test.
     */
    public static function assertSafe(string $environment, ?string $database): void
    {
        if ($environment !== 'testing') {
            throw new RuntimeException(
                "Test suite phải chạy với APP_ENV=testing (đang là '{$environment}'). ".
                'Dừng lại trước khi RefreshDatabase có cơ hội chạy migrate:fresh.'
            );
        }

        if ($database === null || $database === '' || $database === self::DEV_DATABASE) {
            $shown = $database === null || $database === '' ? '(rỗng)' : $database;

            throw new RuntimeException(
                "Test suite đang trỏ vào database không an toàn ('{$shown}'). ".
                "Database development ('".self::DEV_DATABASE."') không được dùng cho test. ".
                'Kiểm tra .env.testing (DB_DATABASE phải khác database dev, không được rỗng).'
            );
        }
    }
}
