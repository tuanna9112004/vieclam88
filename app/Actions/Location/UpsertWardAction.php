<?php

namespace App\Actions\Location;

use App\Models\Ward;
use Illuminate\Support\Facades\DB;

class UpsertWardAction
{
    /**
     * Khóa upsert là `code` (mã GSO). `province_id` bắt buộc và luôn được ghi đè theo lần đồng bộ
     * mới nhất — bọc transaction + lockForUpdate để chạy lại nhiều lần (idempotent) không tạo
     * trùng dưới ghi đồng thời.
     *
     * @param  array{province_id: int, code: string, name: string, is_active: bool}  $data
     */
    public function handle(array $data): Ward
    {
        return DB::transaction(function () use ($data) {
            $existing = Ward::where('code', $data['code'])->lockForUpdate()->first();

            if ($existing) {
                $existing->update($data);

                return $existing;
            }

            return Ward::create($data);
        });
    }
}
