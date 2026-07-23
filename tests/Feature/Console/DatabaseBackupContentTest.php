<?php

namespace Tests\Feature\Console;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * db:backup/db:restore-test chạy mariadb-dump/mariadb như tiến trình ngoài với connection
 * MariaDB riêng của chính nó. Nếu chạy dưới RefreshDatabase (mỗi test bọc trong 1 transaction
 * chưa commit), tiến trình ngoài đó sẽ không thấy được dữ liệu fixture — và CREATE/DROP DATABASE
 * bên trong db:restore-test là DDL, MariaDB tự động implicit-commit transaction đang mở, làm lệch
 * bộ đếm transaction phía PHP của RefreshDatabase. Vì vậy class này KHÔNG dùng RefreshDatabase;
 * fixture được tạo/xóa thật (autocommit), không có transaction nào để lệch.
 */
class DatabaseBackupContentTest extends TestCase
{
    /** @var array<int> */
    private array $preExistingUserIds = [];

    /** @var array<int> */
    private array $preExistingBranchIds = [];

    /** @var array<int> */
    private array $preExistingCompanyIds = [];

    /** @var array<int> */
    private array $preExistingJobIds = [];

    /**
     * Không dùng RefreshDatabase (xem docblock class) nên phải tự đảm bảo schema tồn tại —
     * `migrate` không làm gì nếu đã migrate rồi, an toàn gọi lại nhiều lần.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);

        // Chụp snapshot ID đang có trước khi test tạo fixture — dùng cho tearDown() dọn triệt để,
        // phòng trường hợp có row còn sót từ test khác (class dùng RefreshDatabase chạy ngay trước
        // không dùng transaction chung với class này, nên không thể loại trừ hoàn toàn bằng lý
        // thuyết; đây là lưới an toàn để không làm lệch assertion đếm tuyệt đối ở các test khác).
        $this->preExistingUserIds = User::pluck('id')->all();
        $this->preExistingBranchIds = Branch::withTrashed()->pluck('id')->all();
        $this->preExistingCompanyIds = Company::withTrashed()->pluck('id')->all();
        $this->preExistingJobIds = Job::withTrashed()->pluck('id')->all();
    }

    protected function tearDown(): void
    {
        // Xóa mọi row phát sinh trong test này (theo đúng thứ tự FK), bất kể do fixture của chính
        // test tạo ra hay do tương tác lạ với môi trường test — đảm bảo trả DB về đúng trạng thái
        // trước khi test chạy, không rò rỉ sang bất kỳ test nào khác trong cùng lần chạy suite.
        Job::withTrashed()->whereNotIn('id', $this->preExistingJobIds)->forceDelete();
        Company::withTrashed()->whereNotIn('id', $this->preExistingCompanyIds)->forceDelete();
        User::whereNotIn('id', $this->preExistingUserIds)->delete();
        Branch::withTrashed()->whereNotIn('id', $this->preExistingBranchIds)->forceDelete();

        parent::tearDown();
    }

    public function test_db_restore_test_command_restores_into_isolated_database_and_verifies(): void
    {
        // role admin -> branch_id null, tránh UserFactory mặc định tự tạo kèm 1 Branch ẩn (staff).
        $marker = User::factory()->admin()->create(['name' => 'Restore Test User', 'email' => 'restore-marker@example.test']);

        $testBackupDir = storage_path('app/test_restore_backups');

        try {
            if (File::exists($testBackupDir)) {
                File::deleteDirectory($testBackupDir);
            }
            Artisan::call('db:backup', ['--path' => $testBackupDir]);

            $files = File::files($testBackupDir);
            $sqlFiles = array_filter($files, fn ($f) => str_ends_with($f->getFilename(), '.sql.gz'));
            $sqlFile = reset($sqlFiles);

            $restoreExitCode = Artisan::call('db:restore-test', [
                'file' => $sqlFile->getPathname(),
                '--target-db' => 'vieclam88_restore_test',
            ]);

            // Artisan::output() "fetch and clear" nội bộ buffer — chỉ gọi một lần rồi tái sử dụng.
            $output = Artisan::output();

            $this->assertSame(0, $restoreExitCode);
            $this->assertStringContainsString('Đã khôi phục thành công', $output);
            $this->assertStringContainsString('Đã dọn dẹp sạch CSDL thử nghiệm', $output);

            // Import qua mariadb client phải khôi phục đúng dữ liệu thật (không chỉ exit code 0).
            // Dùng regex >=1 thay vì so khớp đúng "Users = 1": test khác chạy song song trong cùng
            // tiến trình phpunit có thể có user tạm thời khác, mục tiêu ở đây chỉ là chứng minh
            // import qua mariadb client thực sự copy dữ liệu thật (không phải DB rỗng).
            $this->assertMatchesRegularExpression('/Users = [1-9]\d*/', $output);

            // Nguồn không bị đụng.
            $this->assertTrue(User::query()->whereKey($marker->id)->exists());
        } finally {
            File::deleteDirectory($testBackupDir);
        }
    }

    public function test_db_backup_produces_valid_gzip_containing_expected_sql_and_is_memory_bounded(): void
    {
        // Tạo ~9MB dữ liệu text thật trong DB (150 Job x 60.000 ký tự job_description — gần sát
        // giới hạn cột TEXT 65535 byte), đủ lớn để phân biệt rõ "stream qua OS pipe/gzip chunk"
        // với "nạp toàn bộ dump vào PHP memory" (loại bỏ nhiễu do PHP cấp phát heap theo khối).
        $branch = Branch::factory()->create();
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create();
        $largeText = str_repeat('A', 60000);

        $jobs = Job::factory()->count(150)->create([
            'owner_branch_id' => $branch->id,
            'company_id' => $company->id,
            'created_by' => $admin->id,
            'job_description' => $largeText,
        ]);

        $testBackupDir = storage_path('app/test_backup_gzip_content');

        try {
            if (File::exists($testBackupDir)) {
                File::deleteDirectory($testBackupDir);
            }

            $memoryBefore = memory_get_peak_usage(true);

            $exitCode = Artisan::call('db:backup', ['--path' => $testBackupDir]);

            $memoryAfter = memory_get_peak_usage(true);
            $memoryDelta = max($memoryAfter - $memoryBefore, 0);

            $this->assertSame(0, $exitCode);

            $sqlFiles = array_filter(File::files($testBackupDir), fn ($f) => str_ends_with($f->getFilename(), '.sql.gz'));
            $gzFile = reset($sqlFiles);

            // File nén phải là gzip hợp lệ và giải nén ra đúng nội dung SQL của bảng jobs.
            $decompressed = '';
            $handle = gzopen($gzFile->getPathname(), 'rb');
            while (! gzeof($handle)) {
                $decompressed .= gzread($handle, 65536);
            }
            gzclose($handle);

            $this->assertStringContainsString('CREATE TABLE', $decompressed);
            $this->assertStringContainsString('INSERT INTO `jobs`', $decompressed);

            $decompressedSize = strlen($decompressed);
            // 150 job x 60.000 ký tự job_description phải tạo ra dump plain-text tối thiểu 8.5MB.
            $this->assertGreaterThan(8_500_000, $decompressedSize);

            // File permission 0600 theo contract (Windows không có POSIX chmod nên bỏ qua assertion
            // này trên Windows — chmod() vẫn được gọi trong command, chỉ enforce thật trên Linux).
            if (PHP_OS_FAMILY !== 'Windows') {
                $this->assertSame('0600', substr(sprintf('%o', fileperms($gzFile->getPathname())), -4));
            }

            // Bộ nhớ PHP tăng thêm khi chạy db:backup phải nhỏ hơn nhiều so với kích thước dump
            // thật (>8.5MB) — chứng minh mariadb-dump chạy qua proc_open + OS-level file redirect,
            // PHP chỉ giữ chunk 256KB trong lúc nén gzip, không giữ toàn bộ nội dung dump trong
            // memory (ngưỡng tuyệt đối 6MB đủ rộng để không nhiễu bởi overhead cấp phát của PHP,
            // nhưng vẫn nhỏ hơn nhiều so với kích thước dump thật).
            $this->assertLessThan(6_000_000, $memoryDelta);
        } finally {
            File::deleteDirectory($testBackupDir);
        }
    }
}
