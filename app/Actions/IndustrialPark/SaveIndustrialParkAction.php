<?php

namespace App\Actions\IndustrialPark;

use App\Models\AdministrativeUnit;
use App\Models\IndustrialPark;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveIndustrialParkAction
{
    /**
     * @param  array{administrative_unit_id: int, name: string, official_name?: ?string, address_detail?: ?string, is_active?: bool}  $data
     */
    public function handle(array $data, ?IndustrialPark $industrialPark = null): IndustrialPark
    {
        return DB::transaction(function () use ($data, $industrialPark) {
            // Lock đơn vị hành chính đích để serialize việc sinh slug giữa các request đồng thời
            // cùng nhắm vào đơn vị này (tránh 2 request cùng đọc "chưa tồn tại" rồi cùng insert).
            $targetUnit = AdministrativeUnit::whereKey($data['administrative_unit_id'])->lockForUpdate()->first();

            if (! $targetUnit) {
                throw ValidationException::withMessages([
                    'administrative_unit_id' => 'Đơn vị hành chính không tồn tại.',
                ]);
            }

            if ($industrialPark) {
                // Lock chính bản ghi đang sửa để tránh lost update khi 2 request cùng ghi đè.
                $industrialPark = IndustrialPark::whereKey($industrialPark->id)->lockForUpdate()->firstOrFail();
            }

            // FormRequest đã validate trước khi vào Action, nhưng administrative unit có thể đã
            // đổi is_active giữa lúc validate và lúc lock được ở đây — không tin dữ liệu đã
            // validate, tái xác nhận bất biến trên state vừa lock (cùng pattern với
            // UpsertAdministrativeUnitAction::guardAgainstCycle).
            $this->guardAdministrativeUnitInvariant($targetUnit, $industrialPark, $data);

            $data['slug'] = $this->uniqueSlug($data['name'], $targetUnit->id, $industrialPark?->id);

            if ($industrialPark) {
                $industrialPark->update($data);

                return $industrialPark;
            }

            return IndustrialPark::create($data);
        });
    }

    /**
     * @param  array{administrative_unit_id: int, name: string, official_name?: ?string, address_detail?: ?string, is_active?: bool}  $data
     */
    protected function guardAdministrativeUnitInvariant(
        AdministrativeUnit $targetUnit,
        ?IndustrialPark $industrialPark,
        array $data
    ): void {
        $isTransfer = $industrialPark === null || $industrialPark->administrative_unit_id !== $targetUnit->id;
        $resultingIsActive = (bool) ($data['is_active'] ?? true);

        if ($isTransfer && ! $targetUnit->is_active) {
            throw ValidationException::withMessages([
                'administrative_unit_id' => 'Không thể tạo hoặc chuyển khu công nghiệp sang một đơn vị hành chính đã ngừng hoạt động.',
            ]);
        }

        if (! $isTransfer && ! $targetUnit->is_active && $resultingIsActive) {
            throw ValidationException::withMessages([
                'is_active' => 'Đơn vị hành chính hiện tại đã ngừng hoạt động — chỉ có thể tắt hoạt động của khu công nghiệp.',
            ]);
        }
    }

    protected function uniqueSlug(string $name, int $administrativeUnitId, ?int $ignoreId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (
            IndustrialPark::where('administrative_unit_id', $administrativeUnitId)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
