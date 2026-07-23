<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // TASK 1.3: chỉ thêm cột nullable (Expand). Companies chưa có administrative_unit_id
            // để backfill 1:1 — chọn HQ candidate từ nhiều company_locations là việc của TASK 5.2
            // (có xử lý ambiguous), không đoán ở đây. Chưa có form nào ghi cột này.
            $table->foreignId('headquarters_ward_id')->nullable()->after('status')
                ->constrained('wards')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('headquarters_ward_id');
        });
    }
};
