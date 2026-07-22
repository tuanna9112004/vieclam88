<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseRestoreTestCommand extends Command
{
    protected $signature = 'db:restore-test
                            {file : Đường dẫn tới file SQL backup (.sql)}
                            {--target-db=vieclam88_restore_test : Tên CSDL thử nghiệm độc lập}';

    protected $description = 'Thực hiện khôi phục thử nghiệm file backup vào CSDL thử nghiệm độc lập và kiểm tra tính toàn vẹn';

    public function handle(): int
    {
        $this->info('--- BẮT ĐẦU QUY TRÌNH KHÔI PHỤC THỬ NGHIỆM (RESTORE TEST) ---');

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
                $this->error("LỖI TOÀN VẸN CHECKSUM: Expected {$expectedSha}, got {$actualSha}");
                return Command::FAILURE;
            }
            $this->info("✓ Xác minh SHA256 Checksum hợp lệ: {$actualSha}");
        }

        $targetDb = $this->option('target-db');
        $this->warn("⚠️  Thao tác khôi phục sẽ thực hiện trên CSDL THỬ NGHIỆM ĐỘC LẬP: [{$targetDb}]");
        $this->info("Tuyệt đối KHÔNG ghi đè CSDL Production.");

        try {
            // 2. Create isolated target database if not exists
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$targetDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // 3. Switch connection PDO to targetDb
            $config = config('database.connections.mysql');
            $config['database'] = $targetDb;
            config(['database.connections.restore_target' => $config]);
            DB::purge('restore_target');

            $targetDB = DB::connection('restore_target');

            // 4. Import SQL file
            $sql = File::get($filePath);
            $targetDB->statement('SET FOREIGN_KEY_CHECKS=0');
            $statements = array_filter(array_map('trim', explode(";\n", $sql)));
            foreach ($statements as $stmt) {
                if ($stmt !== '' && ! str_starts_with($stmt, '--') && ! str_starts_with($stmt, '/*')) {
                    $targetDB->unprepared($stmt);
                }
            }
            $targetDB->statement('SET FOREIGN_KEY_CHECKS=1');

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

            // 6. Cleanup isolated test database
            $targetDB->statement("DROP DATABASE IF EXISTS `{$targetDb}`");
            $this->info("✓ Đã dọn dẹp sạch CSDL thử nghiệm [{$targetDb}].");

            $this->info('--- HOÀN THÀNH KẾT QUẢ KHÔI PHỤC THỬ NGHIỆM: SUCCESS ---');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('LỖI KHÔI PHỤC THỬ NGHIỆM: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return Command::FAILURE;
        }
    }
}
