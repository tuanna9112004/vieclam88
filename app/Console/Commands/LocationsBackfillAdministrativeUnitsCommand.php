<?php

namespace App\Console\Commands;

use App\Actions\Location\ClassifyAdministrativeUnitMappingAction;
use App\Actions\Location\UpsertAdministrativeUnitMappingAction;
use App\Models\AdministrativeUnit;
use App\Models\AdministrativeUnitMapping;
use App\Models\Province;
use App\Models\Ward;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LocationsBackfillAdministrativeUnitsCommand extends Command
{
    protected $signature = 'locations:backfill-administrative-units
                            {--dry-run : Chỉ tính toán và xuất report, không ghi vào administrative_unit_mappings}
                            {--batch-size=200 : Số bản ghi administrative_units xử lý mỗi lượt}
                            {--resume : Bỏ qua bản ghi đã map thành công (status=mapped) ở lần chạy trước}';

    protected $description = 'Backfill administrative_units sang provinces/wards mới, lưu vào administrative_unit_mappings (TASK 1.2)';

    public function handle(
        ClassifyAdministrativeUnitMappingAction $classify,
        UpsertAdministrativeUnitMappingAction $upsert,
    ): int {
        $isDryRun = (bool) $this->option('dry-run');
        $batchSize = max(1, (int) $this->option('batch-size'));
        $resume = (bool) $this->option('resume');

        $this->info('--- BẮT ĐẦU BACKFILL administrative_units -> provinces/wards ---'.($isDryRun ? ' (DRY-RUN)' : ''));

        $unitsById = AdministrativeUnit::query()->get(['id', 'parent_id', 'official_code', 'name', 'type'])->keyBy('id');
        $provincesByCode = Province::query()->get(['id', 'code'])->keyBy('code');
        $wardsByCode = Ward::query()->get(['id', 'code', 'province_id'])->keyBy('code');

        $skipIds = $resume
            ? array_flip(AdministrativeUnitMapping::query()->where('status', 'mapped')->pluck('administrative_unit_id')->all())
            : [];

        $counts = ['mapped' => 0, 'ambiguous' => 0, 'missing' => 0, 'invalid_parent' => 0];
        $unresolved = [];
        $totalScanned = 0;

        AdministrativeUnit::query()->orderBy('id')->chunkById($batchSize, function ($units) use (
            $classify,
            $upsert,
            $unitsById,
            $provincesByCode,
            $wardsByCode,
            $isDryRun,
            $skipIds,
            &$counts,
            &$unresolved,
            &$totalScanned,
        ) {
            foreach ($units as $unit) {
                $totalScanned++;

                if (isset($skipIds[$unit->id])) {
                    $counts['mapped']++;

                    continue;
                }

                $result = $classify->handle($unit, $unitsById, $provincesByCode, $wardsByCode);
                $counts[$result['status']]++;

                if (! $isDryRun) {
                    $upsert->handle($unit->id, $result);
                }

                if ($result['status'] !== 'mapped') {
                    $unresolved[] = [
                        'administrative_unit_id' => $unit->id,
                        'official_code' => $unit->official_code,
                        'name' => $unit->name,
                        'type' => $unit->type,
                        'status' => $result['status'],
                        'reason' => $result['reason'],
                    ];
                }
            }
        });

        $unresolvedTotal = $counts['ambiguous'] + $counts['missing'] + $counts['invalid_parent'];

        $this->info("Tổng: {$totalScanned} | mapped: {$counts['mapped']} | ambiguous: {$counts['ambiguous']} | missing: {$counts['missing']} | invalid_parent: {$counts['invalid_parent']}");

        if ($totalScanned !== $counts['mapped'] + $unresolvedTotal) {
            $this->error('Tổng mapped + unresolved không khớp tổng đầu vào — dừng, không ghi report.');

            return Command::FAILURE;
        }

        $this->writeReport($counts, $unresolved, $totalScanned, $isDryRun);

        $this->info('--- HOÀN THÀNH ---');

        return Command::SUCCESS;
    }

    /**
     * @param  array<string, int>  $counts
     * @param  list<array<string, mixed>>  $unresolved
     */
    private function writeReport(array $counts, array $unresolved, int $totalScanned, bool $isDryRun): void
    {
        $reportDir = storage_path('app/reports');
        if (! File::exists($reportDir)) {
            File::makeDirectory($reportDir, 0700, true, true);
        }
        @chmod($reportDir, 0700);

        $timestamp = now()->format('Ymd_His');
        $basename = "administrative-unit-mapping-{$timestamp}".($isDryRun ? '_dryrun' : '');

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'dry_run' => $isDryRun,
            'total' => $totalScanned,
            'counts' => $counts,
            'unresolved' => $unresolved,
        ];

        $jsonPath = "{$reportDir}/{$basename}.json";
        File::put($jsonPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        chmod($jsonPath, 0600);

        $csvLines = ['administrative_unit_id,official_code,name,type,status,reason'];
        foreach ($unresolved as $row) {
            $csvLines[] = implode(',', array_map(
                fn ($value): string => '"'.str_replace('"', '""', (string) ($value ?? '')).'"',
                [$row['administrative_unit_id'], $row['official_code'], $row['name'], $row['type'], $row['status'], $row['reason']]
            ));
        }

        $csvPath = "{$reportDir}/{$basename}.csv";
        File::put($csvPath, implode("\n", $csvLines));
        chmod($csvPath, 0600);

        $this->info("Report: {$jsonPath} , {$csvPath}");
    }
}
