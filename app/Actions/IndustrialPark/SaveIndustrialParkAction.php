<?php

namespace App\Actions\IndustrialPark;

use App\Models\IndustrialPark;
use Illuminate\Support\Str;

class SaveIndustrialParkAction
{
    /**
     * @param  array{administrative_unit_id: int, name: string, official_name?: ?string, address_detail?: ?string, is_active?: bool}  $data
     */
    public function handle(array $data, ?IndustrialPark $industrialPark = null): IndustrialPark
    {
        $data['slug'] = $this->uniqueSlug($data['name'], (int) $data['administrative_unit_id'], $industrialPark?->id);

        if ($industrialPark) {
            $industrialPark->update($data);

            return $industrialPark;
        }

        return IndustrialPark::create($data);
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
