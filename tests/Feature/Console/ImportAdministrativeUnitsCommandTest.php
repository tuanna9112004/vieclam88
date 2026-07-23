<?php

namespace Tests\Feature\Console;

use App\Models\AdministrativeUnit;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportAdministrativeUnitsCommandTest extends TestCase
{
    use RefreshDatabase;

    private const IMPORTED_OFFICIAL_CODES = ['1', '4', '8', '27', '901'];

    private function fakeApi(): void
    {
        Http::fake([
            'https://provinces.open-api.vn/api/v2/p/' => Http::response([
                ['name' => 'Thành phố Hà Nội', 'code' => 1, 'division_type' => 'thành phố trung ương', 'codename' => 'ha_noi'],
                ['name' => 'Tỉnh Bắc Ninh', 'code' => 27, 'division_type' => 'tỉnh', 'codename' => 'bac_ninh'],
            ]),
            'https://provinces.open-api.vn/api/v2/p/1*' => Http::response([
                'name' => 'Thành phố Hà Nội', 'code' => 1, 'division_type' => 'thành phố trung ương',
                'wards' => [
                    ['name' => 'Phường Ba Đình', 'code' => 4, 'division_type' => 'phường', 'province_code' => 1],
                    ['name' => 'Phường Ngọc Hà', 'code' => 8, 'division_type' => 'phường', 'province_code' => 1],
                ],
            ]),
            'https://provinces.open-api.vn/api/v2/p/27*' => Http::response([
                'name' => 'Tỉnh Bắc Ninh', 'code' => 27, 'division_type' => 'tỉnh',
                'wards' => [
                    ['name' => 'Xã Yên Phong', 'code' => 901, 'division_type' => 'xã', 'province_code' => 27],
                ],
            ]),
        ]);
    }

    public function test_imports_provinces_and_wards_with_correct_mapping(): void
    {
        $this->fakeApi();

        $exitCode = Artisan::call('administrative-units:import');

        $this->assertSame(0, $exitCode);
        $this->assertSame(
            5,
            AdministrativeUnit::query()->whereIn('official_code', self::IMPORTED_OFFICIAL_CODES)->count()
        );

        $hanoi = AdministrativeUnit::query()->where('official_code', '1')->firstOrFail();
        $this->assertSame('Hà Nội', $hanoi->name);
        $this->assertSame('city', $hanoi->type);
        $this->assertNull($hanoi->parent_id);

        $baDinh = AdministrativeUnit::query()->where('official_code', '4')->firstOrFail();
        $this->assertSame('Ba Đình', $baDinh->name);
        $this->assertSame('ward', $baDinh->type);
        $this->assertSame($hanoi->id, $baDinh->parent_id);

        $bacNinh = AdministrativeUnit::query()->where('official_code', '27')->firstOrFail();
        $this->assertSame('province', $bacNinh->type);

        $yenPhong = AdministrativeUnit::query()->where('official_code', '901')->firstOrFail();
        $this->assertSame('commune', $yenPhong->type);
        $this->assertSame($bacNinh->id, $yenPhong->parent_id);
    }

    public function test_running_twice_is_idempotent_and_updates_existing_rows(): void
    {
        $this->fakeApi();

        Artisan::call('administrative-units:import');
        Artisan::call('administrative-units:import');

        $this->assertSame(
            5,
            AdministrativeUnit::query()->whereIn('official_code', self::IMPORTED_OFFICIAL_CODES)->count()
        );
    }

    public function test_unrecognized_division_type_fails_loudly(): void
    {
        Http::fake([
            'https://provinces.open-api.vn/api/v2/p/' => Http::response([
                ['name' => 'Vùng lạ', 'code' => 99, 'division_type' => 'vùng chưa biết', 'codename' => 'vung_la'],
            ]),
        ]);

        $exitCode = Artisan::call('administrative-units:import');

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertDatabaseMissing('administrative_units', ['official_code' => '99']);
    }
}
