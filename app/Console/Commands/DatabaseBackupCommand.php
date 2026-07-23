<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup
                            {--path= : Thư mục lưu file backup (mặc định storage/app/backups)}
                            {--retention=30 : Số ngày lưu trữ file backup cũ}';

    protected $description = 'Sao lưu CSDL MariaDB qua mariadb-dump, nén gzip streaming, kèm SHA256 checksum và retention an toàn';

    private const FILENAME_PREFIX = 'vieclam88_backup_';

    public function handle(): int
    {
        $this->info('--- BẮT ĐẦU QUY TRÌNH SAO LƯU CSDL MARIADB (mariadb-dump) ---');

        $backupDir = $this->option('path') ?: storage_path('app/backups');
        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0700, true, true);
        }
        @chmod($backupDir, 0700);

        $connectionConfig = $this->resolveConnectionConfig();
        $database = $connectionConfig['database'];

        $timestamp = now()->format('Ymd_His');
        $filename = self::FILENAME_PREFIX."{$timestamp}.sql.gz";
        $filePath = "{$backupDir}/{$filename}";
        $plainSqlPath = "{$backupDir}/.".self::FILENAME_PREFIX."{$timestamp}.sql.tmp";

        $defaultsFile = null;

        try {
            $defaultsFile = $this->writeDefaultsExtraFile($connectionConfig);
            $dumpBinary = config('database.backup.mariadb_dump_binary', 'mariadb-dump');

            $this->info("Đang chạy {$dumpBinary} cho CSDL [{$database}]...");
            $this->runMariadbDumpToPlainFile($dumpBinary, $defaultsFile, $database, $plainSqlPath);

            $this->info('Đang nén gzip theo luồng (streaming)...');
            $this->compressFileToGzip($plainSqlPath, $filePath);

            $sha256 = hash_file('sha256', $filePath);
            $shaFilePath = "{$filePath}.sha256";
            File::put($shaFilePath, "{$sha256}  {$filename}\n");

            chmod($filePath, 0600);
            chmod($shaFilePath, 0600);

            $this->info('✓ Backup thành công: '.number_format(filesize($filePath)).' bytes (nén gzip)');
            $this->info("✓ SHA256 Checksum: {$sha256}");

            $this->applyRetentionPolicy($backupDir, (int) $this->option('retention'));

            $this->info('--- HOÀN THÀNH QUY TRÌNH SAO LƯU CSDL THÀNH CÔNG ---');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            if (File::exists($filePath)) {
                @unlink($filePath);
            }
            $this->error('LỖI SAO LƯU CSDL: '.$e->getMessage());
            return Command::FAILURE;
        } finally {
            if ($defaultsFile !== null && File::exists($defaultsFile)) {
                @unlink($defaultsFile);
            }
            if (File::exists($plainSqlPath)) {
                @unlink($plainSqlPath);
            }
        }
    }

    /**
     * @return array{host: string, port: int|string, username: string, password: string, database: string}
     */
    protected function resolveConnectionConfig(): array
    {
        $connectionName = config('database.default');
        $config = config("database.connections.{$connectionName}");

        return [
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => $config['port'] ?? 3306,
            'username' => $config['username'] ?? 'root',
            'password' => $config['password'] ?? '',
            'database' => $config['database'],
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
     * Chạy mariadb-dump, output stdout được OS redirect thẳng ra file — PHP không giữ
     * nội dung dump trong bộ nhớ ở bước này.
     */
    protected function runMariadbDumpToPlainFile(string $binary, string $defaultsFile, string $database, string $plainSqlPath): void
    {
        $stderrFile = tempnam(sys_get_temp_dir(), 'vieclam88_backup_stderr_');

        $command = [
            $binary,
            '--defaults-extra-file='.$defaultsFile,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            $database,
        ];

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['file', $plainSqlPath, 'w'],
            2 => ['file', $stderrFile, 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes);
        if (! is_resource($process)) {
            @unlink($stderrFile);
            throw new \RuntimeException("Không thể khởi chạy tiến trình {$binary}.");
        }

        fclose($pipes[0]);
        $exitCode = proc_close($process);

        $stderrOutput = File::exists($stderrFile) ? File::get($stderrFile) : '';
        @unlink($stderrFile);

        if ($exitCode !== 0) {
            throw new \RuntimeException("mariadb-dump thất bại (exit {$exitCode}): ".$this->sanitizeProcessMessage($stderrOutput));
        }
    }

    /**
     * Nén file SQL thuần sang .gz theo từng chunk 256KB — bộ nhớ không phụ thuộc kích thước DB.
     */
    protected function compressFileToGzip(string $sourcePath, string $destGzPath): void
    {
        $in = fopen($sourcePath, 'rb');
        $out = gzopen($destGzPath, 'wb9');

        if ($in === false || $out === false) {
            throw new \RuntimeException('Không thể nén file backup sang gzip.');
        }

        while (! feof($in)) {
            $chunk = fread($in, 262144);
            if ($chunk === false || $chunk === '') {
                continue;
            }
            gzwrite($out, $chunk);
        }

        fclose($in);
        gzclose($out);
    }

    /**
     * Chỉ xóa đúng artifact backup (prefix + .sql.gz / .sql.gz.sha256) quá hạn retention,
     * không đụng file khác trong thư mục.
     */
    protected function applyRetentionPolicy(string $backupDir, int $retentionDays): void
    {
        $cutoff = now()->subDays($retentionDays)->timestamp;
        $deletedCount = 0;

        foreach (File::files($backupDir) as $file) {
            $name = $file->getFilename();
            $isBackupArtifact = str_starts_with($name, self::FILENAME_PREFIX)
                && (str_ends_with($name, '.sql.gz') || str_ends_with($name, '.sql.gz.sha256'));

            if ($isBackupArtifact && $file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("✓ Đã dọn dẹp {$deletedCount} file backup cũ vượt quá thời hạn retention ({$retentionDays} ngày).");
        }
    }

    /**
     * Không log đường dẫn defaults-extra-file (gợi ý vị trí credential tạm) hay bất kỳ secret nào.
     */
    protected function sanitizeProcessMessage(string $message): string
    {
        return trim(preg_replace('/--defaults-extra-file=\S+/', '--defaults-extra-file=***', $message) ?? $message);
    }
}
