<?php

namespace App\Actions\Location;

use App\Models\Province;
use Illuminate\Support\Facades\DB;

class UpsertProvinceAction
{
    /**
     * Khóa upsert là `code` (mã GSO). Bọc transaction + lockForUpdate để chạy lại nhiều lần
     * (idempotent) không tạo trùng dưới ghi đồng thời.
     *
     * @param  array{code: string, name: string, is_active: bool}  $data
     */
    public function handle(array $data): Province
    {
        return DB::transaction(function () use ($data) {
            $existing = Province::where('code', $data['code'])->lockForUpdate()->first();

            if ($existing) {
                $existing->update($data);

                return $existing;
            }

            return Province::create($data);
        });
    }
}
