<?php

namespace Tests\Feature\Hr\Job;

use App\Models\Job;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JobRowLockConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * SaveJobVerificationAction, PublishJobAction, ChangeJobStatusAction, ChangeJobBranchAction
     * đều dùng chung 1 pattern: `Job::whereKey($id)->lockForUpdate()->firstOrFail()` bên trong
     * `DB::transaction()`. Test này mở 1 connection PDO THẬT SỰ thứ hai (không mock) tới cùng
     * database test, chứng minh row lock chặn được connection thứ hai đọc-để-khóa cùng dòng khi
     * connection đầu chưa commit — dùng đúng session lock thật của MySQL/MariaDB, không suy đoán
     * từ code.
     */
    public function test_locked_job_row_blocks_concurrent_lock_attempt_from_another_connection(): void
    {
        $job = Job::factory()->create();

        config(['database.connections.job_lock_test_secondary' => config('database.connections.mysql')]);
        $secondConnection = DB::connection('job_lock_test_secondary');
        // Rut ngan thoi gian cho de test khong treo lau (mac dinh InnoDB cho toi 50s).
        $secondConnection->statement('SET SESSION innodb_lock_wait_timeout = 1');

        $blockedByRowLock = false;

        DB::transaction(function () use ($job, $secondConnection, &$blockedByRowLock) {
            // Connection 1 (mac dinh) giu lock trong suot closure nay — giong het cach
            // SaveJobVerificationAction/PublishJobAction/ChangeJobStatusAction/
            // ChangeJobBranchAction dang lam.
            Job::whereKey($job->id)->lockForUpdate()->firstOrFail();

            try {
                // Connection 2 co gang lock cung 1 dong trong luc connection 1 chua commit.
                $secondConnection->transaction(function () use ($secondConnection, $job) {
                    $secondConnection->table('jobs')->where('id', $job->id)->lockForUpdate()->first();
                });
            } catch (QueryException $e) {
                $blockedByRowLock = true;
            }
        });

        $this->assertTrue(
            $blockedByRowLock,
            'Connection thu hai phai bi chan boi row lock cua connection dau (chua commit) — neu khong, lockForUpdate khong thuc su bao ve duoc du lieu khoi race condition.'
        );

        DB::purge('job_lock_test_secondary');
    }
}
