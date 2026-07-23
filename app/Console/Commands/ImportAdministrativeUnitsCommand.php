<?php

namespace App\Console\Commands;

use App\Actions\AdministrativeUnit\UpsertAdministrativeUnitAction;
use App\Services\ProvincesOpenApi\ProvincesOpenApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportAdministrativeUnitsCommand extends Command
{
    protected $signature = 'administrative-units:import';

    protected $description = 'Nhập/đồng bộ tỉnh/thành và phường/xã từ provinces.open-api.vn (API v2) vào administrative_units nội bộ';

    private const PROVINCE_TYPE_MAP = [
        'tỉnh' => 'province',
        'thành phố trung ương' => 'city',
    ];

    private const WARD_TYPE_MAP = [
        'phường' => 'ward',
        'xã' => 'commune',
        'đặc khu' => 'special_zone',
    ];

    public function handle(ProvincesOpenApiClient $client, UpsertAdministrativeUnitAction $action): int
    {
        $this->info('--- BẮT ĐẦU IMPORT ADMINISTRATIVE UNITS TỪ provinces.open-api.vn ---');

        try {
            $provinces = $client->provinces();
        } catch (\Throwable $e) {
            $this->error('Không lấy được danh sách tỉnh/thành: '.$e->getMessage());

            return Command::FAILURE;
        }

        $provinceCount = 0;
        $wardCount = 0;

        foreach ($provinces as $province) {
            try {
                $provinceUnit = $action->handle(
                    $this->unitPayload($client, $province, null, self::PROVINCE_TYPE_MAP)
                );
                $provinceCount++;

                $detail = $client->provinceWithWards((string) $province['code']);

                foreach ($detail['wards'] ?? [] as $ward) {
                    $action->handle(
                        $this->unitPayload($client, $ward, $provinceUnit->id, self::WARD_TYPE_MAP)
                    );
                    $wardCount++;
                }

                $this->info("✓ {$provinceUnit->name}: ".count($detail['wards'] ?? []).' phường/xã');
            } catch (\Throwable $e) {
                $this->error("Lỗi khi nhập tỉnh/thành [{$province['code']}] {$province['name']}: ".$e->getMessage());

                return Command::FAILURE;
            }
        }

        $this->info("--- HOÀN THÀNH: {$provinceCount} tỉnh/thành, {$wardCount} phường/xã ---");

        return Command::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $item  Bản ghi tỉnh hoặc phường/xã thô từ API
     * @param  array<string, string>  $typeMap
     * @return array{official_code: string, parent_id: ?int, name: string, slug: string, type: string, is_active: bool}
     */
    private function unitPayload(ProvincesOpenApiClient $client, array $item, ?int $parentId, array $typeMap): array
    {
        $name = $client->stripNamePrefix($item['name']);

        return [
            'official_code' => (string) $item['code'],
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => $this->mapType($typeMap, $item['division_type']),
            'is_active' => true,
        ];
    }

    /**
     * @param  array<string, string>  $map
     */
    private function mapType(array $map, string $divisionType): string
    {
        $normalized = mb_strtolower($divisionType);

        if (! isset($map[$normalized])) {
            throw new \RuntimeException("Không nhận diện được division_type từ API: \"{$divisionType}\"");
        }

        return $map[$normalized];
    }
}
