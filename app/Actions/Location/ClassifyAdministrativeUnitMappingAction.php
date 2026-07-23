<?php

namespace App\Actions\Location;

use App\Models\AdministrativeUnit;
use Illuminate\Support\Collection;

/**
 * Logic phân loại thuần (không đọc/ghi DB trực tiếp) cho việc map một `administrative_units`
 * sang `provinces`/`wards` mới (TASK 1.2). Map theo `official_code` trước, không đoán bằng tên.
 */
class ClassifyAdministrativeUnitMappingAction
{
    private const LEAF_TYPES = ['commune', 'ward', 'special_zone'];

    private const ROOT_TYPES = ['province', 'city'];

    /**
     * @param  Collection<int, AdministrativeUnit>  $unitsById  keyBy('id') — toàn bộ cây cũ, dùng để tìm root ancestor
     * @param  Collection<string, object{id: int}>  $provincesByCode  keyBy('code')
     * @param  Collection<string, object{id: int, province_id: int}>  $wardsByCode  keyBy('code')
     * @return array{status: string, province_id: ?int, ward_id: ?int, reason: ?string}
     */
    public function handle(
        AdministrativeUnit $unit,
        Collection $unitsById,
        Collection $provincesByCode,
        Collection $wardsByCode,
    ): array {
        if ($unit->type === 'legacy_district') {
            return $this->result('missing', reason: 'legacy_district (cấu trúc 3 cấp cũ) không map trực tiếp sang ward mới, cần xử lý thủ công.');
        }

        if ($unit->official_code === null || $unit->official_code === '') {
            return $this->result('missing', reason: 'official_code rỗng — không map bằng tên để tránh đoán sai.');
        }

        $provinceMatch = $provincesByCode->get($unit->official_code);
        $wardMatch = $wardsByCode->get($unit->official_code);

        if (in_array($unit->type, self::ROOT_TYPES, true)) {
            return $this->classifyRoot($provinceMatch, $wardMatch);
        }

        if (in_array($unit->type, self::LEAF_TYPES, true)) {
            return $this->classifyLeaf($unit, $unitsById, $wardMatch, $provinceMatch, $provincesByCode);
        }

        return $this->result('missing', reason: "type không xác định: {$unit->type}");
    }

    private function classifyRoot(mixed $provinceMatch, mixed $wardMatch): array
    {
        if ($provinceMatch && ! $wardMatch) {
            return $this->result('mapped', provinceId: $provinceMatch->id);
        }

        if ($provinceMatch && $wardMatch) {
            return $this->result('ambiguous', reason: 'official_code khớp cả provinces.code và wards.code.');
        }

        if ($wardMatch) {
            return $this->result('ambiguous', reason: 'Đơn vị gốc (province/city) nhưng official_code khớp wards.code, không khớp provinces.code.');
        }

        return $this->result('missing', reason: 'Không tìm thấy provinces.code khớp official_code.');
    }

    private function classifyLeaf(
        AdministrativeUnit $unit,
        Collection $unitsById,
        mixed $wardMatch,
        mixed $provinceMatch,
        Collection $provincesByCode,
    ): array {
        if ($provinceMatch && $wardMatch) {
            return $this->result('ambiguous', reason: 'official_code khớp cả provinces.code và wards.code.');
        }

        if ($provinceMatch && ! $wardMatch) {
            return $this->result('ambiguous', reason: 'Đơn vị lá (ward/commune) nhưng official_code khớp provinces.code, không khớp wards.code.');
        }

        if (! $wardMatch) {
            return $this->result('missing', reason: 'Không tìm thấy wards.code khớp official_code (có thể đã đổi mã/sáp nhập khi cải cách hành chính 2025).');
        }

        $rootUnit = $this->resolveRootAncestor($unit, $unitsById);

        if ($rootUnit === null) {
            return $this->result('invalid_parent', reason: 'Không xác định được đơn vị gốc trong cây administrative_units cũ (vòng lặp hoặc parent_id gãy).');
        }

        if (! in_array($rootUnit->type, self::ROOT_TYPES, true)) {
            return $this->result(
                'invalid_parent',
                reason: "Đơn vị gốc [{$rootUnit->official_code}] {$rootUnit->name} có type=\"{$rootUnit->type}\" không phải province/city — cây administrative_units cũ bất thường."
            );
        }

        $rootProvinceMatch = $rootUnit->official_code ? $provincesByCode->get($rootUnit->official_code) : null;

        if ($rootProvinceMatch === null) {
            return $this->result(
                'invalid_parent',
                reason: "Đơn vị gốc [{$rootUnit->official_code}] {$rootUnit->name} chưa map được sang provinces, không xác nhận được province cho ward này."
            );
        }

        if ((int) $wardMatch->province_id !== (int) $rootProvinceMatch->id) {
            return $this->result(
                'invalid_parent',
                reason: "wards.code khớp nhưng thuộc province_id={$wardMatch->province_id}, khác province gốc cũ đã map (province_id={$rootProvinceMatch->id})."
            );
        }

        return $this->result('mapped', provinceId: $rootProvinceMatch->id, wardId: $wardMatch->id);
    }

    /**
     * @param  Collection<int, AdministrativeUnit>  $unitsById
     */
    private function resolveRootAncestor(AdministrativeUnit $unit, Collection $unitsById): ?AdministrativeUnit
    {
        $current = $unit;
        $visited = [];

        while ($current->parent_id !== null) {
            if (isset($visited[$current->id])) {
                return null;
            }

            $visited[$current->id] = true;

            $parent = $unitsById->get($current->parent_id);

            if ($parent === null) {
                return null;
            }

            $current = $parent;
        }

        return $current;
    }

    /**
     * @return array{status: string, province_id: ?int, ward_id: ?int, reason: ?string}
     */
    private function result(string $status, ?int $provinceId = null, ?int $wardId = null, ?string $reason = null): array
    {
        return [
            'status' => $status,
            'province_id' => $provinceId,
            'ward_id' => $wardId,
            'reason' => $reason,
        ];
    }
}
