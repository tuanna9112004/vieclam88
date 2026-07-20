<?php

namespace Tests\Feature\Foundation;

use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Models\AdministrativeUnit;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * StoreBranchRequest/UpdateBranchRequest đã có từ Nhóm 1 nhưng chưa có test — chứng minh
 * contract "code unique; name; địa chỉ; phone; Zalo; status" (CTA dùng phone/zalo, phải
 * nullable nhưng không được vượt max length). Route dùng ở đây chỉ tồn tại trong test,
 * không đăng ký vào routes/hr.php — chưa có Controller thật (ngoài phạm vi /db-task).
 */
class BranchRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->post('test/hr/co-so', function (StoreBranchRequest $request) {
            return response()->noContent();
        });

        Route::middleware('web')->put('test/hr/co-so/{branch}', function (UpdateBranchRequest $request, Branch $branch) {
            return response()->noContent();
        });
    }

    public function test_store_requires_code_name_and_administrative_unit(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->from('/dummy')->post('test/hr/co-so', []);

        $response->assertSessionHasErrors(['code', 'name', 'administrative_unit_id']);
    }

    public function test_store_rejects_duplicate_code(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);
        Branch::factory()->create(['code' => 'HN-01']);

        $response = $this->actingAs($admin)->from('/dummy')->post('test/hr/co-so', [
            'code' => 'HN-01',
            'name' => 'Chi nhánh mới',
            'administrative_unit_id' => $unit->id,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_store_rejects_inactive_administrative_unit(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => false]);

        $response = $this->actingAs($admin)->from('/dummy')->post('test/hr/co-so', [
            'code' => 'HN-02',
            'name' => 'Chi nhánh mới',
            'administrative_unit_id' => $unit->id,
        ]);

        $response->assertSessionHasErrors('administrative_unit_id');
    }

    public function test_store_allows_valid_payload_with_optional_phone_and_zalo_omitted(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->from('/dummy')->post('test/hr/co-so', [
            'code' => 'HN-03',
            'name' => 'Chi nhánh mới',
            'administrative_unit_id' => $unit->id,
        ]);

        $response->assertSessionDoesntHaveErrors();
    }

    public function test_store_rejects_phone_and_zalo_exceeding_max_length(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->from('/dummy')->post('test/hr/co-so', [
            'code' => 'HN-04',
            'name' => 'Chi nhánh mới',
            'administrative_unit_id' => $unit->id,
            'phone' => str_repeat('9', 21),
            'zalo' => str_repeat('9', 21),
        ]);

        $response->assertSessionHasErrors(['phone', 'zalo']);
    }

    public function test_update_requires_status_to_be_a_defined_enum_value(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create();
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->from('/dummy')->put("test/hr/co-so/{$branch->id}", [
            'code' => $branch->code,
            'name' => $branch->name,
            'administrative_unit_id' => $unit->id,
            'status' => 'archived',
        ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_update_ignores_current_branch_own_code_when_checking_uniqueness(): void
    {
        $admin = User::factory()->admin()->create();
        $branch = Branch::factory()->create(['code' => 'HN-05']);
        $unit = AdministrativeUnit::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->from('/dummy')->put("test/hr/co-so/{$branch->id}", [
            'code' => 'HN-05',
            'name' => 'Ten moi',
            'administrative_unit_id' => $unit->id,
            'status' => 'active',
        ]);

        $response->assertSessionDoesntHaveErrors();
    }
}
