<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Support\CsvSanitizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BranchSeeder extends Seeder
{
    /**
     * Mã cơ sở là natural key; không gắn ward khi chưa có mapping code đã xác minh.
     *
     * @var array<string, string>
     */
    private const BRANCHES = [
        'VP' => 'Chi nhánh Vĩnh Phúc',
        'PT' => 'Văn phòng đại diện Phú Thọ',
        'HB' => 'Chi nhánh Hòa Bình',
        'BGBN' => 'Chi nhánh Bắc Giang - Bắc Ninh',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::BRANCHES as $code => $name) {
                $branch = Branch::withTrashed()
                    ->whereRaw('UPPER(TRIM(code)) = ?', [$code])
                    ->lockForUpdate()
                    ->first() ?? new Branch;
                $isNew = ! $branch->exists;

                if ($branch->exists && $branch->trashed()) {
                    $branch->restore();
                }

                $branch->code = $code;
                $branch->name = $name;
                if ($isNew) {
                    $branch->status = 'active';
                }
                $branch->save();
            }
        });

        [$jsonPath, $csvPath] = $this->writeLegacyReport();

        $this->command?->info("Branch legacy report: {$jsonPath}, {$csvPath}");
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function writeLegacyReport(): array
    {
        $canonicalTokens = collect(self::BRANCHES)
            ->map(fn (string $name): array => $this->significantTokens($name));

        $legacyBranches = Branch::withTrashed()
            ->whereNotIn('code', array_keys(self::BRANCHES))
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'status', 'deleted_at'])
            ->map(function (Branch $branch) use ($canonicalTokens): array {
                $tokens = $this->significantTokens($branch->name);
                $possibleMatches = $canonicalTokens
                    ->map(function (array $targetTokens, string $code) use ($tokens): ?array {
                        $sharedTokens = array_values(array_intersect($tokens, $targetTokens));

                        if (count($sharedTokens) < 2) {
                            return null;
                        }

                        $score = count($sharedTokens) / max(1, min(count($tokens), count($targetTokens)));

                        return [
                            'code' => $code,
                            'name' => self::BRANCHES[$code],
                            'shared_tokens' => $sharedTokens,
                            'score' => round($score, 2),
                        ];
                    })
                    ->filter()
                    ->sortByDesc('score')
                    ->values()
                    ->all();

                return [
                    'id' => $branch->id,
                    'code' => $branch->code,
                    'name' => $branch->name,
                    'status' => $branch->status,
                    'deleted_at' => $branch->deleted_at?->toIso8601String(),
                    'classification' => $possibleMatches === [] ? 'legacy' : 'possible_duplicate',
                    'possible_matches' => $possibleMatches,
                ];
            })
            ->all();

        $report = [
            'generated_at' => now()->toIso8601String(),
            'canonical_codes' => array_keys(self::BRANCHES),
            'counts' => [
                'canonical' => count(self::BRANCHES),
                'legacy' => count($legacyBranches),
                'possible_duplicates' => collect($legacyBranches)
                    ->where('classification', 'possible_duplicate')
                    ->count(),
            ],
            'branches' => $legacyBranches,
            'note' => 'Report chỉ để rà soát/merge thủ công; seeder không xóa hoặc tự merge branch cũ.',
        ];

        $reportDir = storage_path('app/reports');
        if (! File::exists($reportDir)) {
            File::makeDirectory($reportDir, 0700, true, true);
        }
        @chmod($reportDir, 0700);

        $basename = 'branch-seed-duplicates-'.now()->format('Ymd_His_u');
        $jsonPath = "{$reportDir}/{$basename}.json";
        $csvPath = "{$reportDir}/{$basename}.csv";

        File::put(
            $jsonPath,
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );

        $csvLines = ['id,code,name,status,deleted_at,classification,possible_match_codes'];
        foreach ($legacyBranches as $branch) {
            $csvLines[] = $this->csvRow([
                $branch['id'],
                $branch['code'],
                $branch['name'],
                $branch['status'],
                $branch['deleted_at'],
                $branch['classification'],
                collect($branch['possible_matches'])->pluck('code')->implode('|'),
            ]);
        }
        File::put($csvPath, implode("\n", $csvLines));

        @chmod($jsonPath, 0600);
        @chmod($csvPath, 0600);

        return [$jsonPath, $csvPath];
    }

    /**
     * @return list<string>
     */
    private function significantTokens(string $name): array
    {
        $ignored = [
            'chi', 'nhanh', 'van', 'phong', 'dai', 'dien', 'co', 'so',
            'cong', 'ty', 'tnhh', 'vieclam88',
        ];
        $tokens = preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($name)), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_diff($tokens ?: [], $ignored)));
    }

    /**
     * @param  list<mixed>  $values
     */
    private function csvRow(array $values): string
    {
        return implode(',', array_map(
            function (mixed $value): string {
                $sanitized = CsvSanitizer::escape($value === null ? null : (string) $value);

                return '"'.str_replace('"', '""', $sanitized).'"';
            },
            $values
        ));
    }
}
