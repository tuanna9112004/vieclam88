<?php

namespace App\Actions\Location;

use App\Models\AdministrativeUnitMapping;
use Illuminate\Support\Facades\DB;

class UpsertAdministrativeUnitMappingAction
{
    /**
     * Khóa upsert là `administrative_unit_id`. Bọc transaction + lockForUpdate để chạy lại
     * nhiều lần (resume/idempotent) không tạo trùng dưới ghi đồng thời.
     *
     * @param  array{status: string, province_id: ?int, ward_id: ?int, reason: ?string}  $classification
     */
    public function handle(int $administrativeUnitId, array $classification): AdministrativeUnitMapping
    {
        return DB::transaction(function () use ($administrativeUnitId, $classification) {
            $existing = AdministrativeUnitMapping::where('administrative_unit_id', $administrativeUnitId)
                ->lockForUpdate()
                ->first();

            $data = [
                'administrative_unit_id' => $administrativeUnitId,
                'province_id' => $classification['province_id'],
                'ward_id' => $classification['ward_id'],
                'status' => $classification['status'],
                'reason' => $classification['reason'],
                'mapped_at' => $classification['status'] === 'mapped' ? now() : null,
            ];

            if ($existing) {
                $existing->update($data);

                return $existing;
            }

            return AdministrativeUnitMapping::create($data);
        });
    }
}
