<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\TestDatabaseGuard;
use Tests\TestCase;

/**
 * Kết hợp với GuardOrderingTest (chứng minh createApplication() chạy trước
 * refreshDatabase()) — test này chứng minh nếu tên database khớp database
 * development, guard sẽ chặn. Không kết nối/sửa database dev thật — chỉ gọi
 * trực tiếp TestDatabaseGuard::assertSafe() (đúng hàm mà createApplication()
 * gọi) với giá trị mô phỏng.
 */
class GuardBlocksUnsafeDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_throws_when_database_name_matches_development_database(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("'".TestDatabaseGuard::DEV_DATABASE."'");

        TestDatabaseGuard::assertSafe(app()->environment(), TestDatabaseGuard::DEV_DATABASE);
    }

    public function test_current_real_test_config_is_actually_safe(): void
    {
        // Đối chứng: xác nhận config THẬT của chính test này (không mô phỏng)
        // là an toàn — nhánh "pass" của guard khớp với môi trường test thật.
        $connection = DB::connection()->getName();
        $database = config("database.connections.{$connection}.database");

        $this->assertNotSame(TestDatabaseGuard::DEV_DATABASE, $database);
        $this->assertSame('testing', app()->environment());

        TestDatabaseGuard::assertSafe(app()->environment(), $database);

        $this->addToAssertionCount(1);
    }
}
