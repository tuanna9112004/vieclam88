<?php

namespace Database\Factories;

use App\Models\AdministrativeUnit;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * TASK 1.3: `ward_id` mặc định để trống (không tự tạo Ward/Province lồng nhau) — Branch là
     * factory phụ thuộc rất phổ biến trong toàn bộ test suite (Job/Application/User...), tạo
     * thêm 2 bản ghi mới mỗi lần sẽ làm chậm và không cần thiết cho các test không liên quan tới
     * địa chỉ. Test cần ward cụ thể (địa chỉ mới) truyền `ward_id` tường minh.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('BR-###')),
            'name' => 'Chi nhánh '.fake()->city(),
            'phone' => '09'.fake()->numerify('########'),
            'zalo' => null,
            'email' => null,
            'administrative_unit_id' => AdministrativeUnit::factory(),
            'ward_id' => null,
            'address_detail' => fake()->streetAddress(),
            'status' => 'active',
        ];
    }
}
