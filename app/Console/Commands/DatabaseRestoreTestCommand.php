<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseRestoreTestCommand extends Command
{
    protected $signature = 'db:restore-test
                            {file : Đường dẫn tới file SQL backup nén gzip (.sql.gz)}
                            {--target-db=vieclam88_restore_test : Tên CSDL thử nghiệm độc lập}';

    protected $description = 'Thực hiện khôi phục thử nghiệm file backup vào CSDL thử nghiệm độc lập và kiểm tra tính toàn vẹn';

    /**
     * Bắt buộc một trong hai contract đặt tên để không thể nhầm với DB thật.
     */
    private const REQUIRED_SUFFIX = '_restore_test';
    private const REQUIRED_PREFIX = 'vieclam88_restore_test_';
    private const SAFE_IDENTIFIER_PATTERN = '/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/';

    public function handle(): int
    {
        $this->info('--- BẮT ĐẦU QUY TRÌNH KHÔI PHỤC THỬ NGHIỆM (RESTORE TEST) ---');

        // Guard 0: fail-closed tuyệt đối trên production, không có đường bypass.
        if (app()->environment('production')) {
            $this->error('Từ chối: lệnh db:restore-test bị khóa cứng trên môi trường production.');
            return Command::FAILURE;
        }

        $filePath = $this->argument('file');
        if (! File::exists($filePath)) {
            $this->error("File backup không tồn tại: {$filePath}");
            return Command::FAILURE;
        }

        // 1. Verification of SHA256 Checksum if file.sha256 exists
        $shaFilePath = "{$filePath}.sha256";
        if (File::exists($shaFilePath)) {
            $expectedSha = trim(explode(' ', File::get($shaFilePath))[0]);
            $actualSha = hash_file('sha256', $filePath);

            if ($expectedSha !== $actualSha) {
                $this->error('LỖI TOÀN VẸN CHECKSUM: file backup không khớp checksum đã lưu.');
                return Command::FAILURE;
            }
            $this->info("✓ Xác minh SHA256 Checksum hợp lệ: {$actualSha}");
        }

        $targetDb = $this->option('target-db');

        // Guard 1: validate identifier TRƯỚC khi target-db được nội suy vào bất kỳ SQL/log nào.
        try {
            $this->assertTargetDatabaseIsSafe($targetDb);
        } catch (\RuntimeException $e) {
            $this->error('GUARD CHẶN: '.$e->getMessage());
            return Command::FAILURE;
        }

        $this->warn("⚠️  Thao tác khôi phục sẽ thực hiện trên CSDL THỬ NGHIỆM ĐỘC LẬP: [{$targetDb}]");
        $this->info('Tuyệt đối KHÔNG ghi đè CSDL Production.');

        $targetCreated = false;
        $targetDB = null;
        $defaultsFile = null;

        try {
            // 2. Create isolated target database if not exists
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$targetDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $targetCreated = true;

            // 3. Switch connection PDO to targetDb (dùng cho verification query sau restore)
            $config = config('database.connections.mysql');
            $config['database'] = $targetDb;
            config(['database.connections.restore_target' => $config]);
            DB::purge('restore_target');

            $targetDB = DB::connection('restore_target');

            // 4. Import qua gzip stream + mariadb client (không tự parse SQL, không tải cả file vào bộ nhớ)
            $connectionConfig = $this->resolveConnectionConfig($config);
            $defaultsFile = $this->writeDefaultsExtraFile($connectionConfig);
            $clientBinary = config('database.backup.mariadb_client_binary', 'mariadb');

            $this->info("Đang import qua {$clientBinary} (gzip stream)...");
            $this->streamGzipIntoMariadbClient($clientBinary, $defaultsFile, $targetDb, $filePath);

            // 5. Verification Checklist post-restore
            $restoredTables = $targetDB->select('SHOW TABLES');
            $dbKey = "Tables_in_{$targetDb}";
            $tableNames = array_map(fn($t) => $t->$dbKey ?? current((array)$t), $restoredTables);

            $this->info("✓ Đã khôi phục thành công " . count($tableNames) . " bảng vào CSDL thử nghiệm [{$targetDb}].");

            // Verify core tables count
            $expectedCore = ['users', 'branches', 'jobs', 'applications', 'candidates'];
            foreach ($expectedCore as $coreTable) {
                if (! in_array($coreTable, $tableNames, true)) {
                    $this->error("LỖI TOÀN VẸN: Thiếu bảng cốt lõi [{$coreTable}] sau khi khôi phục!");
                    return Command::FAILURE;
                }
            }

            $userCount = $targetDB->table('users')->count();
            $jobCount = $targetDB->table('jobs')->count();
            $appCount = $targetDB->table('applications')->count();

            $this->info("✓ Kiểm tra số lượng bản ghi: Users = {$userCount}, Jobs = {$jobCount}, Applications = {$appCount}");

            $this->info('--- HOÀN THÀNH KẾT QUẢ KHÔI PHỤC THỬ NGHIỆM: SUCCESS ---');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('LỖI KHÔI PHỤC THỬ NGHIỆM: ' . $e->getMessage());
            return Command::FAILURE;
        } finally {
            // 6. Cleanup — chỉ DROP đúng DB target đã tạo và đã qua guard, không bao giờ đụng DB nguồn.
            if ($targetCreated) {
                try {
                    ($targetDB ?? DB::connection('restore_target'))->statement("DROP DATABASE IF EXISTS `{$targetDb}`");
                    $this->info("✓ Đã dọn dẹp sạch CSDL thử nghiệm [{$targetDb}].");
                } catch (\Throwable $cleanupError) {
                    $this->error("CẢNH BÁO: Không thể dọn dẹp CSDL thử nghiệm [{$targetDb}]: " . $cleanupError->getMessage());
                }
            }
            if ($defaultsFile !== null && File::exists($defaultsFile)) {
                @unlink($defaultsFile);
            }
        }
    }

    /**
     * @return array{host: string, port: int|string, username: string, password: string}
     */
    protected function resolveConnectionConfig(array $config): array
    {
        return [
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => $config['port'] ?? 3306,
            'username' => $config['username'] ?? 'root',
            'password' => $config['password'] ?? '',
        ];
    }

    /**
     * Ghi credential vào defaults-extra-file tạm (0600), không truyền password qua CLI args/env.
     */
    protected function writeDefaultsExtraFile(array $connectionConfig): string
    {
        $path = tempnam(sys_get_temp_dir(), 'vieclam88_mariadb_');
        if ($path === false) {
            throw new \RuntimeException('Không thể tạo file defaults-extra-file tạm.');
        }

        $ini = "[client]\n"
            ."host={$connectionConfig['host']}\n"
            ."port={$connectionConfig['port']}\n"
            ."user={$connectionConfig['username']}\n"
            ."password={$connectionConfig['password']}\n";

        File::put($path, $ini);
        chmod($path, 0600);

        return $path;
    }

    /**
     * Đọc file .sql.gz theo chunk 256KB và feed vào stdin của mariadb client đang chạy;
     * stdout/stderr của client được OS redirect thẳng ra file — không cần đọc đồng thời,
     * tránh deadlock pipe và không tải toàn bộ nội dung backup vào bộ nhớ PHP.
     */
    protected function streamGzipIntoMariadbClient(string $binary, string $defaultsFile, string $targetDb, string $gzFilePath): void
    {
        $stderrFile = tempnam(sys_get_temp_dir(), 'vieclam88_restore_stderr_');

        $command = [$binary, '--defaults-extra-file='.$defaultsFile, $targetDb];

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['file', $stderrFile, 'w'],
            2 => ['file', $stderrFile, 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes);
        if (! is_resource($process)) {
            @unlink($stderrFile);
            throw new \RuntimeException("Không thể khởi chạy tiến trình {$binary}.");
        }

        $gz = gzopen($gzFilePath, 'rb');
        if ($gz === false) {
            proc_terminate($process);
            proc_close($process);
            @unlink($stderrFile);
            throw new \RuntimeException('Không thể mở file backup gzip để đọc.');
        }

        try {
            while (! gzeof($gz)) {
                $chunk = gzread($gz, 262144);
                if ($chunk === false) {
                    break;
                }
                if ($chunk !== '') {
                    fwrite($pipes[0], $chunk);
                }
            }
        } finally {
            gzclose($gz);
            fclose($pipes[0]);
        }

        $exitCode = proc_close($process);
        $stderrOutput = File::exists($stderrFile) ? File::get($stderrFile) : '';
        @unlink($stderrFile);

        if ($exitCode !== 0) {
            throw new \RuntimeException("mariadb import thất bại (exit {$exitCode}): ".$this->sanitizeProcessMessage($stderrOutput));
        }
    }

    /**
     * Không log đường dẫn defaults-extra-file (gợi ý vị trí credential tạm) hay bất kỳ secret nào.
     */
    protected function sanitizeProcessMessage(string $message): string
    {
        return trim(preg_replace('/--defaults-extra-file=\S+/', '--defaults-extra-file=***', $message) ?? $message);
    }

    /**
     * Chặn mọi target-db nguy hiểm TRƯỚC khi nó được dùng trong bất kỳ câu lệnh SQL nào.
     *
     * @throws \RuntimeException
     */
    protected function assertTargetDatabaseIsSafe(string $targetDb): void
    {
        if (! preg_match(self::SAFE_IDENTIFIER_PATTERN, $targetDb)) {
            throw new \RuntimeException(
                'Tên CSDL target không hợp lệ: chỉ cho phép chữ cái/số/gạch dưới, bắt đầu bằng chữ cái, tối đa 64 ký tự.'
            );
        }

        $hasSafeSuffix = str_ends_with($targetDb, self::REQUIRED_SUFFIX);
        $hasSafePrefix = str_starts_with($targetDb, self::REQUIRED_PREFIX);

        if (! $hasSafeSuffix && ! $hasSafePrefix) {
            throw new \RuntimeException(
                'Tên CSDL target phải có suffix "'.self::REQUIRED_SUFFIX.'" hoặc prefix "'.self::REQUIRED_PREFIX.'".'
            );
        }

        $protectedDatabases = $this->collectConfiguredDatabaseNames();

        if (in_array(strtolower($targetDb), $protectedDatabases, true)) {
            throw new \RuntimeException(
                'Tên CSDL target trùng với CSDL đang được cấu hình (nguồn/dev/test/staging/production) — bị chặn để tránh ghi đè.'
            );
        }
    }

    /**
     * Thu thập tên DB từ mọi connection đã cấu hình + connection đang hoạt động,
     * để không cho target-db trùng bất kỳ CSDL thật nào.
     *
     * @return array<int, string>
     */
    protected function collectConfiguredDatabaseNames(): array
    {
        $names = collect(config('database.connections'))
            ->pluck('database')
            ->filter(fn ($db) => is_string($db) && $db !== '')
            ->map(fn ($db) => strtolower($db));

        try {
            $names->push(strtolower(DB::connection()->getDatabaseName()));
        } catch (\Throwable) {
            // Không có connection hoạt động — bỏ qua, các bước sau sẽ tự thất bại an toàn.
        }

        return $names->unique()->values()->all();
    }
}
