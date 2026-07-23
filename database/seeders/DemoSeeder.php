<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Dữ liệu demo đã được loại bỏ; giữ class để các lệnh/tham chiếu cũ không bị lỗi.
     */
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException(
                'DemoSeeder chỉ được phép chạy trong môi trường local hoặc testing. APP_ENV hiện tại: '.app()->environment()
            );
        }
    }
}
