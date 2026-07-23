<?php

namespace App\Console\Commands;

use App\Actions\AdministrativeUnit\UpsertAdministrativeUnitAction;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportAdministrativeUnitsCommand extends Command
{
    protected $signature = 'administrative-units:import';

    protected $description = 'Nhập/đồng bộ tỉnh/thành và phường/xã từ provinces.open-api.vn (API v2) vào administrative_units nội bộ';

    private const API_BASE_URL = 'https://provinces.open-api.vn/api/v2';

    /**
     * ADR-079: prefix hành chính gắn trong field "name" của API, cắt bỏ để khớp convention tên
     * hiện có trong administrative_units (vd "Hà Nội", không phải "Thành phố Hà Nội").
     */
    private const NAME_PREFIXES = [
        'Thành phố ',
        'Tỉnh ',
        'Phường ',
        'Xã ',
        'Đặc khu ',
    ];

    private const PROVINCE_TYPE_MAP = [
        'tỉnh' => 'province',
        'thành phố trung ương' => 'city',
    ];

    private const WARD_TYPE_MAP = [
        'phường' => 'ward',
        'xã' => 'commune',
        'đặc khu' => 'special_zone',
    ];

    public function handle(UpsertAdministrativeUnitAction $action): int
    {
        $this->info('--- BẮT ĐẦU IMPORT ADMINISTRATIVE UNITS TỪ provinces.open-api.vn ---');

        $http = Http::timeout(15)->retry(2, 500);

        try {
            $provinces = $this->fetch($http, self::API_BASE_URL.'/p/');
        } catch (\Throwable $e) {
            $this->error('Không lấy được danh sách tỉnh/thành: '.$e->getMessage());

            return Command::FAILURE;
        }

        $provinceCount = 0;
        $wardCount = 0;

        foreach ($provinces as $province) {
            try {
                $provinceUnit = $action->handle(
                    $this->unitPayload($province, null, self::PROVINCE_TYPE_MAP)
                );
                $provinceCount++;

                $detail = $this->fetch($http, self::API_BASE_URL."/p/{$province['code']}?depth=2");

                foreach ($detail['wards'] ?? [] as $ward) {
                    $action->handle(
                        $this->unitPayload($ward, $provinceUnit->id, self::WARD_TYPE_MAP)
                    );
                    $wardCount++;
                }

                $this->info("✓ {$provinceUnit->name}: ".count($detail['wards'] ?? [])." phường/xã");
            } catch (\Throwable $e) {
                $this->error("Lỗi khi nhập tỉnh/thành [{$province['code']}] {$province['name']}: ".$e->getMessage());

                return Command::FAILURE;
            }
        }

        $this->info("--- HOÀN THÀNH: {$provinceCount} tỉnh/thành, {$wardCount} phường/xã ---");

        return Command::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetch(PendingRequest $http, string $url): array
    {
        return $http->get($url)->throw()->json();
    }

    /**
     * @param  array<string, mixed>  $item  Bản ghi tỉnh hoặc phường/xã thô từ API
     * @param  array<string, string>  $typeMap
     * @return array{official_code: string, parent_id: ?int, name: string, slug: string, type: string, is_active: bool}
     */
    private function unitPayload(array $item, ?int $parentId, array $typeMap): array
    {
        $name = $this->stripPrefix($item['name']);

        return [
            'official_code' => (string) $item['code'],
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => $this->mapType($typeMap, $item['division_type']),
            'is_active' => true,
        ];
    }

    private function stripPrefix(string $name): string
    {
        foreach (self::NAME_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return substr($name, strlen($prefix));
            }
        }

        return $name;
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
