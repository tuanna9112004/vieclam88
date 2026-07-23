<?php

namespace App\Actions\AdministrativeUnit;

use App\Models\AdministrativeUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpsertAdministrativeUnitAction
{
    /**
     * ADR-070: khóa upsert là official_code khi có giá trị, (parent_id, slug)/root_slug_key
     * (ADR-065) chỉ khi KHÔNG có official_code — 2 khóa loại trừ nhau, không fallback chéo
     * (fallback chéo có thể khớp nhầm sang bản ghi của một đơn vị khác cùng slug và âm thầm
     * ghi đè official_code của nó). Bọc transaction + lockForUpdate để check-rồi-ghi (bao gồm
     * duyệt chuỗi ancestor chống cycle) là atomic dưới ghi đồng thời.
     *
     * @param  array{official_code?: ?string, parent_id?: ?int, name: string, slug: string, type: string, is_active?: bool, valid_from?: ?string, valid_to?: ?string}  $data
     * @param  AdministrativeUnit|null  $target  Bản ghi route-bound cần cập nhật; null thì dùng khóa upsert ADR-070.
     */
    public function handle(array $data, ?AdministrativeUnit $target = null): AdministrativeUnit
    {
        return DB::transaction(function () use ($data, $target) {
            $existing = $target
                ? AdministrativeUnit::whereKey($target->getKey())->lockForUpdate()->firstOrFail()
                : $this->resolveExisting($data);

            if ($existing && ! $target) {
                $existing = AdministrativeUnit::whereKey($existing->id)->lockForUpdate()->first();
            }

            if ($existing && array_key_exists('parent_id', $data) && $data['parent_id'] !== null) {
                $this->guardAgainstCycle($existing, (int) $data['parent_id']);
            }

            if ($existing) {
                $existing->update($data);

                return $existing;
            }

            return AdministrativeUnit::create($data);
        });
    }

    protected function resolveExisting(array $data): ?AdministrativeUnit
    {
        if (! empty($data['official_code'])) {
            return AdministrativeUnit::where('official_code', $data['official_code'])->first();
        }

        return AdministrativeUnit::where('parent_id', $data['parent_id'] ?? null)
            ->where('slug', $data['slug'])
            ->first();
    }

    protected function guardAgainstCycle(AdministrativeUnit $unit, int $newParentId): void
    {
        if ($newParentId === $unit->id) {
            throw ValidationException::withMessages([
                'parent_id' => 'Đơn vị hành chính không thể là cha của chính nó.',
            ]);
        }

        $ancestorId = $newParentId;
        $visited = [];

        while ($ancestorId !== null) {
            if ($ancestorId === $unit->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Không thể gán parent tạo thành vòng lặp trong cây đơn vị hành chính.',
                ]);
            }

            // Dữ liệu hiện hữu có thể đã chứa cycle không liên quan tới $unit; dừng khi gặp lại
            // một ancestor đã duyệt để tránh vòng lặp vô hạn thay vì lan truyền cycle cũ.
            if (isset($visited[$ancestorId])) {
                break;
            }

            $visited[$ancestorId] = true;

            $ancestorId = AdministrativeUnit::whereKey($ancestorId)->lockForUpdate()->value('parent_id');
        }
    }
}
