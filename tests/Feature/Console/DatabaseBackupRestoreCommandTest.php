<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseBackupRestoreCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_db_backup_command_creates_backup_sql_file_and_sha256_checksum(): void
    {
        User::factory()->create(['name' => 'Backup Test User']);

        $testBackupDir = storage_path('app/test_backups');
        if (File::exists($testBackupDir)) {
            File::deleteDirectory($testBackupDir);
        }

        $exitCode = Artisan::call('db:backup', [
            '--path' => $testBackupDir,
            '--retention' => 30,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertTrue(File::exists($testBackupDir));

        $files = File::files($testBackupDir);
        $sqlFiles = array_filter($files, fn($f) => str_ends_with($f->getFilename(), '.sql'));
        $shaFiles = array_filter($files, fn($f) => str_ends_with($f->getFilename(), '.sha256'));

        $this->assertNotEmpty($sqlFiles);
        $this->assertNotEmpty($shaFiles);

        $sqlFile = reset($sqlFiles);
        $shaFile = reset($shaFiles);

        // Verify SHA256 matches
        $expectedSha = trim(explode(' ', File::get($shaFile->getPathname()))[0]);
        $actualSha = hash_file('sha256', $sqlFile->getPathname());
        $this->assertSame($expectedSha, $actualSha);

        // Cleanup
        File::deleteDirectory($testBackupDir);
    }

    public function test_db_restore_test_command_restores_into_isolated_database_and_verifies(): void
    {
        User::factory()->create(['name' => 'Restore Test User']);

        $testBackupDir = storage_path('app/test_restore_backups');
        if (File::exists($testBackupDir)) {
            File::deleteDirectory($testBackupDir);
        }
        Artisan::call('db:backup', ['--path' => $testBackupDir]);

        $files = File::files($testBackupDir);
        $sqlFiles = array_filter($files, fn($f) => str_ends_with($f->getFilename(), '.sql'));
        $sqlFile = reset($sqlFiles);

        // Test restore into isolated database `vieclam88_restore_test`
        $restoreExitCode = Artisan::call('db:restore-test', [
            'file' => $sqlFile->getPathname(),
            '--target-db' => 'vieclam88_restore_test',
        ]);

        $this->assertSame(0, $restoreExitCode);

        // Cleanup
        File::deleteDirectory($testBackupDir);
    }
}
