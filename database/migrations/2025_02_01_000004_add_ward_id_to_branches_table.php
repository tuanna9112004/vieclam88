<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('ward_id')->nullable()->after('administrative_unit_id')
                ->constrained('wards')->restrictOnDelete();
        });

        // TASK 1.3 (SWITCH): form mới chỉ chọn province -> ward, không còn gửi
        // administrative_unit_id — nới NOT NULL để record mới có thể tạo mà không cần giá trị cũ.
        // Dữ liệu hiện có giữ nguyên; đọc mới ưu tiên ward_id, fallback administrative_unit_id.
        DB::statement('ALTER TABLE branches MODIFY administrative_unit_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        // CHÚ Ý: nếu đã có branch tạo/sửa sau migration này với administrative_unit_id NULL (form
        // mới không còn gửi cột này), lệnh MODIFY...NOT NULL bên dưới sẽ FAIL (strict mode) —
        // phải backfill administrative_unit_id cho các bản ghi đó trước khi rollback.
        DB::statement('ALTER TABLE branches MODIFY administrative_unit_id BIGINT UNSIGNED NOT NULL');

        Schema::table('branches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ward_id');
        });
    }
};
