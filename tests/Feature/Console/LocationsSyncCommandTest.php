<?php

namespace Tests\Feature\Console;

use App\Models\Province;
use App\Models\Ward;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocationsSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    private const HANOI_CODE = '1';

    private const BAC_NINH_CODE = '27';

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

    public function test_syncs_provinces_and_wards_with_stripped_names_and_correct_relation(): void
    {
        $this->fakeApi();

        $exitCode = Artisan::call('locations:sync');

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame(2, Province::count());
        $this->assertSame(3, Ward::count());

        $hanoi = Province::query()->where('code', self::HANOI_CODE)->firstOrFail();
        $this->assertSame('Hà Nội', $hanoi->name);
        $this->assertTrue($hanoi->is_active);

        $baDinh = Ward::query()->where('code', '4')->firstOrFail();
        $ngocHa = Ward::query()->where('code', '8')->firstOrFail();
        $this->assertSame('Ba Đình', $baDinh->name);
        $this->assertSame($hanoi->id, $baDinh->province_id, 'Ward phải thuộc đúng province');
        $this->assertSame($hanoi->id, $ngocHa->province_id, 'Ward phải thuộc đúng province');

        $bacNinh = Province::query()->where('code', self::BAC_NINH_CODE)->firstOrFail();
        $yenPhong = Ward::query()->where('code', '901')->firstOrFail();
        $this->assertSame($bacNinh->id, $yenPhong->province_id);
    }

    public function test_running_twice_is_idempotent_and_updates_existing_rows_without_duplicates(): void
    {
        $this->fakeApi();

        Artisan::call('locations:sync');
        $firstRunProvinceIds = Province::query()->pluck('id')->sort()->values();
        $firstRunWardIds = Ward::query()->pluck('id')->sort()->values();

        Artisan::call('locations:sync');

        $this->assertSame(2, Province::count());
        $this->assertSame(3, Ward::count());
        $this->assertTrue($firstRunProvinceIds->diff(Province::query()->pluck('id'))->isEmpty(), 'Không được tạo province mới khi chạy lại');
        $this->assertTrue($firstRunWardIds->diff(Ward::query()->pluck('id'))->isEmpty(), 'Không được tạo ward mới khi chạy lại');
    }

    public function test_inactive_records_are_excluded_from_active_selection_query(): void
    {
        $province = Province::factory()->create(['is_active' => true]);
        $inactiveProvince = Province::factory()->create(['is_active' => false]);
        Ward::factory()->create(['province_id' => $province->id, 'is_active' => true]);
        $inactiveWard = Ward::factory()->create(['province_id' => $province->id, 'is_active' => false]);

        $activeProvinceIds = Province::query()->where('is_active', true)->pluck('id');
        $activeWardIds = Ward::query()->where('is_active', true)->pluck('id');

        $this->assertTrue($activeProvinceIds->contains($province->id));
        $this->assertFalse($activeProvinceIds->contains($inactiveProvince->id));
        $this->assertFalse($activeWardIds->contains($inactiveWard->id));
    }

    public function test_province_list_fetch_failure_returns_failure_and_writes_nothing(): void
    {
        Http::fake([
            'https://provinces.open-api.vn/api/v2/p/' => Http::response('server error', 500),
        ]);

        $exitCode = Artisan::call('locations:sync');

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertSame(0, Province::count());
        $this->assertSame(0, Ward::count());
    }

    public function test_ward_detail_fetch_failure_mid_loop_keeps_prior_province_committed_and_creates_no_orphan_ward(): void
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
                ],
            ]),
            'https://provinces.open-api.vn/api/v2/p/27*' => Http::response('server error', 500),
        ]);

        $exitCode = Artisan::call('locations:sync');

        $this->assertSame(Command::FAILURE, $exitCode);

        // Tỉnh/thành xử lý trước lỗi vẫn được ghi nhận đầy đủ (mỗi upsert là transaction riêng).
        $this->assertSame(1, Ward::query()->where('code', '4')->count());
        $hanoi = Province::query()->where('code', self::HANOI_CODE)->firstOrFail();
        $this->assertSame($hanoi->id, Ward::query()->where('code', '4')->value('province_id'));

        // Không có ward mồ côi: mọi ward tồn tại đều phải trỏ đúng một province có thật.
        $this->assertSame(0, Ward::query()->whereNotIn('province_id', Province::query()->pluck('id'))->count());
    }

    public function test_wards_table_rejects_duplicate_code_at_database_level(): void
    {
        $province = Province::factory()->create();
        Ward::factory()->create(['province_id' => $province->id, 'code' => '999']);

        $this->expectException(QueryException::class);
        Ward::factory()->create(['province_id' => $province->id, 'code' => '999']);
    }

    public function test_wards_table_rejects_null_province_id_at_database_level(): void
    {
        $this->expectException(QueryException::class);

        DB::table('wards')->insert([
            'province_id' => null,
            'code' => '998',
            'name' => 'Không có tỉnh',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_provinces_table_rejects_duplicate_code_at_database_level(): void
    {
        Province::factory()->create(['code' => '777']);

        $this->expectException(QueryException::class);
        Province::factory()->create(['code' => '777']);
    }
}
