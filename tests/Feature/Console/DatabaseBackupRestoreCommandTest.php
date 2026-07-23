<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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
        $sqlFiles = array_filter($files, fn($f) => str_ends_with($f->getFilename(), '.sql.gz'));
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

    public function test_db_restore_test_rejects_target_matching_the_currently_connected_source_database(): void
    {
        $sourceDatabase = DB::connection()->getDatabaseName();

        $testBackupDir = storage_path('app/test_restore_guard_backups');
        if (File::exists($testBackupDir)) {
            File::deleteDirectory($testBackupDir);
        }
        Artisan::call('db:backup', ['--path' => $testBackupDir]);
        $sqlFiles = array_filter(File::files($testBackupDir), fn ($f) => str_ends_with($f->getFilename(), '.sql.gz'));
        $sqlFile = reset($sqlFiles);

        $marker = User::factory()->create(['name' => 'Guard Marker User']);

        $exitCode = Artisan::call('db:restore-test', [
            'file' => $sqlFile->getPathname(),
            '--target-db' => $sourceDatabase,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('GUARD CHẶN', Artisan::output());
        // Nguồn không bị đụng: record vẫn còn nguyên, DB nguồn không bị DROP/ghi đè.
        $this->assertTrue(User::query()->whereKey($marker->id)->exists());

        File::deleteDirectory($testBackupDir);
    }

    public function test_db_restore_test_rejects_target_with_unsafe_sql_characters(): void
    {
        $testBackupDir = storage_path('app/test_restore_guard_unsafe');
        if (File::exists($testBackupDir)) {
            File::deleteDirectory($testBackupDir);
        }
        Artisan::call('db:backup', ['--path' => $testBackupDir]);
        $sqlFiles = array_filter(File::files($testBackupDir), fn ($f) => str_ends_with($f->getFilename(), '.sql.gz'));
        $sqlFile = reset($sqlFiles);

        $exitCode = Artisan::call('db:restore-test', [
            'file' => $sqlFile->getPathname(),
            '--target-db' => 'vieclam88_restore_test`; DROP DATABASE mysql; --',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('GUARD CHẶN', Artisan::output());

        File::deleteDirectory($testBackupDir);
    }

    public function test_db_restore_test_rejects_target_missing_required_suffix_or_prefix(): void
    {
        $testBackupDir = storage_path('app/test_restore_guard_naming');
        if (File::exists($testBackupDir)) {
            File::deleteDirectory($testBackupDir);
        }
        Artisan::call('db:backup', ['--path' => $testBackupDir]);
        $sqlFiles = array_filter(File::files($testBackupDir), fn ($f) => str_ends_with($f->getFilename(), '.sql.gz'));
        $sqlFile = reset($sqlFiles);

        $exitCode = Artisan::call('db:restore-test', [
            'file' => $sqlFile->getPathname(),
            '--target-db' => 'some_random_database',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('GUARD CHẶN', Artisan::output());

        File::deleteDirectory($testBackupDir);
    }

    public function test_db_restore_test_is_blocked_unconditionally_when_app_env_is_production(): void
    {
        $this->app['env'] = 'production';

        $exitCode = Artisan::call('db:restore-test', [
            'file' => storage_path('app/does-not-matter.sql'),
            '--target-db' => 'vieclam88_restore_test',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('production', Artisan::output());
    }
}
