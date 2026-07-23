<?php

namespace Tests\Feature\Hr;

use App\Models\Branch;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gate end-to-end Giai đoạn 3 (Branch + Staff): 1 kịch bản liên tục demo toàn bộ chuỗi hành vi
 * theo yêu cầu verify-task Gate — không lặp lại các test đơn lẻ đã có ở BranchManagementTest/
 * StaffManagementTest/HrLoginTest/PasswordChangeTest, chỉ nối chúng thành 1 luồng thật.
 */
class Phase3GateTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_3_gate_end_to_end_scenario(): void
    {
        $admin = User::factory()->admin()->create();
        $ward = Ward::factory()->create(['is_active' => true]);

        // 1-2. Admin tạo Branch A và Branch B qua route thật.
        $this->actingAs($admin)->post(route('hr.branches.store'), [
            'code' => 'BR-A',
            'name' => 'Chi nhánh A',
            'ward_id' => $ward->id,
        ])->assertRedirect(route('hr.branches.index'));

        $this->actingAs($admin)->post(route('hr.branches.store'), [
            'code' => 'BR-B',
            'name' => 'Chi nhánh B',
            'ward_id' => $ward->id,
        ])->assertRedirect(route('hr.branches.index'));

        $branchA = Branch::where('code', 'BR-A')->firstOrFail();
        $branchB = Branch::where('code', 'BR-B')->firstOrFail();

        // 3-4. Admin tạo Staff A thuộc Branch A và Staff B thuộc Branch B qua route thật.
        $this->actingAs($admin)->post(route('hr.staff.store'), [
            'name' => 'Nhan vien A',
            'email' => 'staff-a@vieclam88.test',
            'password' => 'temp-password-a1',
            'branch_id' => $branchA->id,
        ])->assertRedirect(route('hr.staff.index'));

        $this->actingAs($admin)->post(route('hr.staff.store'), [
            'name' => 'Nhan vien B',
            'email' => 'staff-b@vieclam88.test',
            'password' => 'temp-password-b1',
            'branch_id' => $branchB->id,
        ])->assertRedirect(route('hr.staff.index'));

        $staffA = User::where('email', 'staff-a@vieclam88.test')->firstOrFail();
        $staffB = User::where('email', 'staff-b@vieclam88.test')->firstOrFail();

        $this->assertSame($branchA->id, $staffA->branch_id);
        $this->assertSame($branchB->id, $staffB->branch_id);

        // 5. Staff A đăng nhập bằng mật khẩu tạm, bị chặn ở password-first-change, hoàn thành
        // đổi mật khẩu thì mới vào được HR. actingAs(admin) ở trên giữ session admin cho tới khi
        // logout tường minh — guest middleware sẽ chặn login.store nếu còn phiên admin.
        $this->post(route('hr.logout'));

        $this->post(route('hr.login.store'), [
            'email' => $staffA->email,
            'password' => 'temp-password-a1',
        ])->assertRedirect(route('hr.dashboard'));

        $this->assertAuthenticatedAs($staffA);

        $this->get(route('hr.dashboard'))->assertRedirect(route('hr.password.change'));

        $this->put(route('hr.password.update'), [
            'password' => 'staff-a-new-password',
            'password_confirmation' => 'staff-a-new-password',
        ])->assertRedirect(route('hr.dashboard'));

        $this->get(route('hr.dashboard'))->assertOk();

        // 6. Staff A không thể tự đổi branch_id sang Branch B.
        $this->put(route('hr.staff.update', $staffA), [
            'name' => $staffA->name,
            'email' => $staffA->email,
            'branch_id' => $branchB->id,
        ])->assertForbidden();

        $this->assertSame($branchA->id, $staffA->fresh()->branch_id);

        // 7. Staff A không thể truy cập module Branch.
        $this->get(route('hr.branches.index'))->assertForbidden();

        // 8. Staff A không thể truy cập module Staff Management.
        $this->get(route('hr.staff.index'))->assertForbidden();

        // 9. Staff A không thể xem hoặc sửa tài khoản Staff B qua direct URL — không có rò
        // dữ liệu/quyền giữa Branch A và Branch B.
        $this->get(route('hr.staff.edit', $staffB))->assertForbidden();

        $this->put(route('hr.staff.update', $staffB), [
            'name' => 'Bi doi ten',
            'email' => $staffB->email,
            'branch_id' => $branchB->id,
        ])->assertForbidden();

        $this->assertSame('Nhan vien B', $staffB->fresh()->name);

        // 10. Admin khóa Staff A — request kế tiếp của Staff A mất quyền ngay (session hiện tại
        // bị invalidate bởi EnsureUserIsActive, không chỉ chặn ở lần login sau).
        $this->actingAs($admin)->post(route('hr.staff.lock', $staffA))
            ->assertRedirect(route('hr.staff.index'));

        $this->assertSame('locked', $staffA->fresh()->status);

        $this->actingAs($staffA->fresh())->get(route('hr.dashboard'))
            ->assertRedirect(route('hr.login'));

        // 11. Unlock hoạt động đúng — Staff A lấy lại quyền truy cập bình thường.
        $this->actingAs($admin)->post(route('hr.staff.unlock', $staffA))
            ->assertRedirect(route('hr.staff.index'));

        $this->assertSame('active', $staffA->fresh()->status);

        $this->actingAs($staffA->fresh())->get(route('hr.dashboard'))->assertOk();
    }
}
