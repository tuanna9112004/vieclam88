<?php

namespace App\Services\ProvincesOpenApi;

use Illuminate\Support\Facades\Http;

/**
 * Nguồn fetch/normalize duy nhất cho provinces.open-api.vn (API v2). Dùng chung bởi
 * `administrative-units:import` (ghi vào `administrative_units`) và `locations:sync`
 * (ghi vào `provinces`/`wards`) để không có hai implementation API lệch nhau (TASK 1.1).
 */
class ProvincesOpenApiClient
{
    public const API_BASE_URL = 'https://provinces.open-api.vn/api/v2';

    /**
     * ADR-079: prefix hành chính gắn trong field "name" của API, cắt bỏ để khớp convention tên
     * hiện có (vd "Hà Nội", không phải "Thành phố Hà Nội").
     */
    private const NAME_PREFIXES = [
        'Thành phố ',
        'Tỉnh ',
        'Phường ',
        'Xã ',
        'Đặc khu ',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function provinces(): array
    {
        return $this->get(self::API_BASE_URL.'/p/');
    }

    /**
     * @return array<string, mixed>
     */
    public function provinceWithWards(string $code): array
    {
        return $this->get(self::API_BASE_URL."/p/{$code}?depth=2");
    }

    public function stripNamePrefix(string $name): string
    {
        foreach (self::NAME_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return substr($name, strlen($prefix));
            }
        }

        return $name;
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $url): array
    {
        return Http::timeout(15)->retry(2, 500)->get($url)->throw()->json();
    }
}
