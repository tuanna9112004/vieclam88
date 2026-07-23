<?php

namespace App\Actions\Location;

use App\Models\AdministrativeUnitMapping;
use Illuminate\Database\Eloquent\Model;

/**
 * TASK 1.3: đọc administrative_unit_mappings (TASK 1.2) để backfill FK ward mới trên một bảng
 * nghiệp vụ. Chỉ cập nhật bản ghi đang có administrative_unit_id nhưng chưa có ward tương ứng —
 * không ghi đè ward đã chọn thủ công qua form mới.
 */
class BackfillWardForeignKeyAction
{
    /**
     * @param  class-string<Model>  $modelClass
     * @return array{updated: int, unresolved: list<array<string, mixed>>}
     */
    public function handle(
        string $modelClass,
        string $administrativeUnitColumn,
        string $wardColumn,
        bool $dryRun,
        int $batchSize,
    ): array {
        $updated = 0;
        $unresolved = [];

        $modelClass::query()
            ->whereNotNull($administrativeUnitColumn)
            ->whereNull($wardColumn)
            ->orderBy('id')
            ->chunkById($batchSize, function ($records) use (
                $administrativeUnitColumn,
                $wardColumn,
                $dryRun,
                &$updated,
                &$unresolved,
            ) {
                foreach ($records as $record) {
                    $administrativeUnitId = $record->{$administrativeUnitColumn};
                    $mapping = AdministrativeUnitMapping::where('administrative_unit_id', $administrativeUnitId)->first();

                    if ($mapping === null || $mapping->status !== 'mapped' || $mapping->ward_id === null) {
                        $unresolved[] = [
                            'id' => $record->id,
                            'administrative_unit_id' => $administrativeUnitId,
                            'reason' => $mapping === null
                                ? 'Không có bản ghi trong administrative_unit_mappings — chạy locations:backfill-administrative-units trước.'
                                : "Mapping status={$mapping->status}, chưa xác nhận ward.",
                        ];

                        continue;
                    }

                    if (! $dryRun) {
                        $record->forceFill([$wardColumn => $mapping->ward_id])->save();
                    }

                    $updated++;
                }
            });

        return ['updated' => $updated, 'unresolved' => $unresolved];
    }
}
