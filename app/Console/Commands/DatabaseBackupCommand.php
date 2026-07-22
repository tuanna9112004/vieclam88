<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup
                            {--path= : Thư mục lưu file backup (mặc định storage/app/backups)}
                            {--retention=30 : Số ngày lưu trữ file backup cũ}';

    protected $description = 'Thực hiện sao lưu CSDL MariaDB/MySQL kèm SHA256 checksum và quản lý retention';

    public function handle(): int
    {
        $this->info('--- BẮT ĐẦU QUY TRÌNH SAO LƯU CSDL MARIADB/MYSQL ---');

        $backupDir = $this->option('path') ?: storage_path('app/backups');
        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0700, true, true);
        }

        $dbName = config('database.connections.mysql.database') ?? config('database.connections.mariadb.database');
        $timestamp = now()->format('Ymd_His');
        $filename = "backup_{$dbName}_{$timestamp}.sql";
        $filePath = "{$backupDir}/{$filename}";

        $this->info("Đang tạo bản sao lưu CSDL [{$dbName}] vào file: {$filePath}...");

        try {
            $tables = DB::select('SHOW TABLES');
            $dbKey = "Tables_in_{$dbName}";

            $sqlContent = "-- Vieclam88 MariaDB/MySQL Database Backup\n";
            $sqlContent .= "-- Created at: " . now()->toIso8601String() . "\n";
            $sqlContent .= "-- Database: {$dbName}\n\n";
            $sqlContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            $topologicalOrder = [
                'administrative_units',
                'branches',
                'users',
                'industrial_parks',
                'companies',
                'company_locations',
                'company_contacts',
                'jobs',
                'job_locations',
                'job_verifications',
                'job_status_histories',
                'job_branch_histories',
                'work_shifts',
                'recruitment_sources',
                'settings',
                'job_work_shifts',
                'candidates',
                'candidate_contacts',
                'applications',
                'candidate_duplicate_reviews',
                'application_status_histories',
                'application_contact_attempts',
                'application_appointments',
                'application_branch_histories',
                'application_notes',
                'export_logs',
                'migrations',
            ];

            $allTableNames = array_map(fn($t) => $t->$dbKey ?? current((array)$t), $tables);

            usort($allTableNames, function ($a, $b) use ($topologicalOrder) {
                $posA = array_search($a, $topologicalOrder, true);
                $posB = array_search($b, $topologicalOrder, true);
                if ($posA !== false && $posB !== false) return $posA <=> $posB;
                if ($posA !== false) return -1;
                if ($posB !== false) return 1;
                return strcmp($a, $b);
            });

            foreach ($allTableNames as $table) {

                // Structure
                $createStmt = DB::select("SHOW CREATE TABLE `{$table}`")[0]->{'Create Table'};
                $sqlContent .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sqlContent .= $createStmt . ";\n\n";

                // Data
                $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
                $insertableCols = [];
                foreach ($columns as $col) {
                    $extra = strtolower($col->Extra ?? '');
                    if (! str_contains($extra, 'generated') && ! str_contains($extra, 'stored') && ! str_contains($extra, 'virtual')) {
                        $insertableCols[] = $col->Field;
                    }
                }

                $rows = DB::table($table)->get();
                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $filteredRow = array_intersect_key($rowArray, array_flip($insertableCols));

                    $values = array_map(function ($val) {
                        if ($val === null) return 'NULL';
                        return DB::getPdo()->quote($val);
                    }, $filteredRow);

                    $cols = array_map(fn($c) => "`{$c}`", array_keys($filteredRow));
                    $sqlContent .= "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $values) . ");\n";
                }
                $sqlContent .= "\n";
            }

            $sqlContent .= "SET FOREIGN_KEY_CHECKS=1;\n";

            File::put($filePath, $sqlContent);

            // Compute SHA256 Checksum
            $sha256 = hash_file('sha256', $filePath);
            $shaFilePath = "{$filePath}.sha256";
            File::put($shaFilePath, "{$sha256}  {$filename}\n");

            $this->info("✓ Tạo file SQL backup thành công: " . number_format(filesize($filePath)) . " bytes");
            $this->info("✓ SHA256 Checksum: {$sha256}");

            // Clean old backups according to retention policy
            $retentionDays = (int) $this->option('retention');
            $cutoff = now()->subDays($retentionDays)->timestamp;
            $deletedCount = 0;

            foreach (File::files($backupDir) as $file) {
                if ($file->getMTime() < $cutoff) {
                    File::delete($file->getPathname());
                    $deletedCount++;
                }
            }

            if ($deletedCount > 0) {
                $this->info("✓ Đã dọn dẹp {$deletedCount} file backup cũ vượt quá thời hạn retention ({$retentionDays} ngày).");
            }

            $this->info('--- HOÀN THÀNH QUY TRÌNH SAO LƯU CSDL THÀNH CÔNG ---');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('LỖI SAO LƯU CSDL: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
