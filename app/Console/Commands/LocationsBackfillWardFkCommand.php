<?php

namespace App\Console\Commands;

use App\Actions\Location\BackfillWardForeignKeyAction;
use App\Models\Branch;
use App\Models\Candidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LocationsBackfillWardFkCommand extends Command
{
    protected $signature = 'locations:backfill-ward-fk
                            {--dry-run : Chỉ tính toán và xuất report, không ghi FK ward}
                            {--batch-size=200 : Số bản ghi xử lý mỗi lượt cho mỗi bảng}';

    protected $description = 'Backfill branches.ward_id/candidates.current_ward_id từ administrative_unit_mappings (TASK 1.3)';

    /**
     * companies.headquarters_ward_id CHỦ Ý không có ở đây — companies chưa có
     * administrative_unit_id để backfill 1:1; chọn HQ candidate từ company_locations là việc của
     * TASK 5.2 (có xử lý ambiguous), backfill ở đây sẽ là đoán dữ liệu.
     *
     * @var array<string, array{0: class-string, 1: string, 2: string}>
     */
    private const TARGETS = [
        'branches' => [Branch::class, 'administrative_unit_id', 'ward_id'],
        'candidates' => [Candidate::class, 'current_administrative_unit_id', 'current_ward_id'],
    ];

    public function handle(BackfillWardForeignKeyAction $action): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $batchSize = max(1, (int) $this->option('batch-size'));

        $this->info('--- BẮT ĐẦU BACKFILL FK ward (branches, candidates) ---'.($isDryRun ? ' (DRY-RUN)' : ''));

        $report = [
            'generated_at' => now()->toIso8601String(),
            'dry_run' => $isDryRun,
            'tables' => [],
        ];

        foreach (self::TARGETS as $table => [$modelClass, $administrativeUnitColumn, $wardColumn]) {
            $result = $action->handle($modelClass, $administrativeUnitColumn, $wardColumn, $isDryRun, $batchSize);

            $this->info("{$table}: updated={$result['updated']} unresolved=".count($result['unresolved']));

            $report['tables'][$table] = [
                'updated' => $result['updated'],
                'unresolved_count' => count($result['unresolved']),
                'unresolved' => $result['unresolved'],
            ];
        }

        $report['tables']['companies'] = [
            'updated' => 0,
            'unresolved_count' => 0,
            'unresolved' => [],
            'note' => 'Chủ ý bỏ qua ở TASK 1.3 — chưa có administrative_unit_id nguồn, chọn HQ candidate thuộc TASK 5.2.',
        ];

        $this->writeReport($report, $isDryRun);

        $this->info('--- HOÀN THÀNH ---');

        return Command::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function writeReport(array $report, bool $isDryRun): void
    {
        $reportDir = storage_path('app/reports');
        if (! File::exists($reportDir)) {
            File::makeDirectory($reportDir, 0700, true, true);
        }
        @chmod($reportDir, 0700);

        $timestamp = now()->format('Ymd_His');
        $basename = "ward-fk-backfill-{$timestamp}".($isDryRun ? '_dryrun' : '');

        $jsonPath = "{$reportDir}/{$basename}.json";
        File::put($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        chmod($jsonPath, 0600);

        $csvLines = ['table,id,administrative_unit_id,reason'];
        foreach ($report['tables'] as $table => $data) {
            foreach ($data['unresolved'] as $row) {
                $csvLines[] = implode(',', array_map(
                    fn ($value): string => '"'.str_replace('"', '""', (string) ($value ?? '')).'"',
                    [$table, $row['id'], $row['administrative_unit_id'], $row['reason']]
                ));
            }
        }

        $csvPath = "{$reportDir}/{$basename}.csv";
        File::put($csvPath, implode("\n", $csvLines));
        chmod($csvPath, 0600);

        $this->info("Report: {$jsonPath} , {$csvPath}");
    }
}
