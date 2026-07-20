<?php

namespace Tests\Feature\Foundation;

use App\Actions\IndustrialPark\SaveIndustrialParkAction;
use App\Models\AdministrativeUnit;
use App\Models\IndustrialPark;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SaveIndustrialParkActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Mô phỏng race: FormRequest đã validate administrative_unit_id lúc còn active, nhưng đơn
     * vị bị deactivate trước khi Action thực sự chạy — gọi thẳng Action (bỏ qua FormRequest) để
     * chứng minh Action tự tái xác nhận, không tin hoàn toàn dữ liệu đã validate trước đó.
     */
    public function test_create_rejects_when_target_administrative_unit_became_inactive_before_action_ran(): void
    {
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);
        $unit->update(['is_active' => false]);

        $this->expectException(ValidationException::class);

        try {
            (new SaveIndustrialParkAction)->handle([
                'administrative_unit_id' => $unit->id,
                'name' => 'Khu công nghiệp Race',
            ]);
        } finally {
            $this->assertDatabaseMissing('industrial_parks', ['name' => 'Khu công nghiệp Race']);
        }
    }

    public function test_update_rejects_activating_park_after_its_administrative_unit_became_inactive(): void
    {
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);
        $park = IndustrialPark::factory()->create([
            'administrative_unit_id' => $unit->id,
            'name' => 'Ten goc',
            'is_active' => false,
        ]);

        $unit->update(['is_active' => false]);

        $this->expectException(ValidationException::class);

        try {
            (new SaveIndustrialParkAction)->handle([
                'administrative_unit_id' => $unit->id,
                'name' => 'Ten goc',
                'is_active' => true,
            ], $park);
        } finally {
            $fresh = $park->fresh();
            $this->assertSame('Ten goc', $fresh->name);
            $this->assertFalse($fresh->is_active);
        }
    }

    public function test_update_keeping_inactive_parent_and_deactivating_park_succeeds(): void
    {
        $unit = AdministrativeUnit::factory()->create(['is_active' => false]);
        $park = IndustrialPark::factory()->create([
            'administrative_unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $updated = (new SaveIndustrialParkAction)->handle([
            'administrative_unit_id' => $unit->id,
            'name' => $park->name,
            'is_active' => false,
        ], $park);

        $this->assertFalse($updated->fresh()->is_active);
    }

    public function test_update_rejects_transferring_to_a_different_inactive_administrative_unit(): void
    {
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);
        $otherInactiveUnit = AdministrativeUnit::factory()->create(['is_active' => false]);
        $park = IndustrialPark::factory()->create(['administrative_unit_id' => $unit->id, 'is_active' => true]);

        $this->expectException(ValidationException::class);

        try {
            (new SaveIndustrialParkAction)->handle([
                'administrative_unit_id' => $otherInactiveUnit->id,
                'name' => $park->name,
                'is_active' => false,
            ], $park);
        } finally {
            $this->assertSame($unit->id, $park->fresh()->administrative_unit_id);
        }
    }

    public function test_update_allows_transferring_to_a_different_active_administrative_unit(): void
    {
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);
        $newUnit = AdministrativeUnit::factory()->create(['is_active' => true]);
        $park = IndustrialPark::factory()->create(['administrative_unit_id' => $unit->id, 'is_active' => true]);

        $updated = (new SaveIndustrialParkAction)->handle([
            'administrative_unit_id' => $newUnit->id,
            'name' => $park->name,
            'is_active' => true,
        ], $park);

        $this->assertSame($newUnit->id, $updated->fresh()->administrative_unit_id);
    }

    /**
     * PHPUnit/RefreshDatabase chạy đơn luồng, một kết nối DB — không thể mô phỏng 2 request
     * thực sự đồng thời. Test này chứng minh vế còn lại của bảo vệ concurrency: uniqueSlug()
     * đọc state hiện tại (không cache) trong cùng transaction đã lock administrative unit, nên
     * một bản ghi "vừa được ghi bởi giao dịch khác" (mô phỏng bằng cách tạo sẵn trước khi gọi
     * Action) luôn được nhìn thấy và tránh trùng slug — kết hợp với UNIQUE(administrative_unit_id,
     * slug) ở DB làm backstop cuối cùng nếu có race thật giữa các connection khác nhau.
     */
    public function test_slug_generation_sees_freshly_committed_rows_under_the_same_lock(): void
    {
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);
        IndustrialPark::factory()->create([
            'administrative_unit_id' => $unit->id,
            'name' => 'Khu Concurrent',
            'slug' => 'khu-concurrent',
        ]);

        $created = (new SaveIndustrialParkAction)->handle([
            'administrative_unit_id' => $unit->id,
            'name' => 'Khu Concurrent',
        ]);

        $this->assertSame('khu-concurrent-2', $created->slug);
        $this->assertDatabaseCount('industrial_parks', 2);
    }
}
