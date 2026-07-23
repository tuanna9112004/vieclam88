<?php

namespace App\Console\Commands;

use App\Actions\Location\UpsertProvinceAction;
use App\Actions\Location\UpsertWardAction;
use App\Services\ProvincesOpenApi\ProvincesOpenApiClient;
use Illuminate\Console\Command;

class LocationsSyncCommand extends Command
{
    protected $signature = 'locations:sync';

    protected $description = 'Đồng bộ tỉnh/thành (provinces) và phường/xã (wards) từ provinces.open-api.vn (API v2) vào bảng provinces/wards (TASK 1.1)';

    public function handle(
        ProvincesOpenApiClient $client,
        UpsertProvinceAction $upsertProvince,
        UpsertWardAction $upsertWard,
    ): int {
        $this->info('--- BẮT ĐẦU ĐỒNG BỘ PROVINCES/WARDS TỪ provinces.open-api.vn ---');

        try {
            $provinces = $client->provinces();
        } catch (\Throwable $e) {
            $this->error('Không lấy được danh sách tỉnh/thành: '.$e->getMessage());

            return Command::FAILURE;
        }

        $provinceCount = 0;
        $wardCount = 0;

        foreach ($provinces as $item) {
            try {
                $province = $upsertProvince->handle([
                    'code' => (string) $item['code'],
                    'name' => $client->stripNamePrefix($item['name']),
                    'is_active' => true,
                ]);
                $provinceCount++;

                $detail = $client->provinceWithWards((string) $item['code']);

                foreach ($detail['wards'] ?? [] as $wardItem) {
                    $upsertWard->handle([
                        'province_id' => $province->id,
                        'code' => (string) $wardItem['code'],
                        'name' => $client->stripNamePrefix($wardItem['name']),
                        'is_active' => true,
                    ]);
                    $wardCount++;
                }

                $this->info("✓ {$province->name}: ".count($detail['wards'] ?? []).' phường/xã');
            } catch (\Throwable $e) {
                $this->error("Lỗi khi đồng bộ tỉnh/thành [{$item['code']}] {$item['name']}: ".$e->getMessage());

                return Command::FAILURE;
            }
        }

        $this->info("--- HOÀN THÀNH: {$provinceCount} tỉnh/thành, {$wardCount} phường/xã ---");

        return Command::SUCCESS;
    }
}
