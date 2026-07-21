<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
            $table->foreignId('company_location_id')->constrained('company_locations')->restrictOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->unique(['job_id', 'company_location_id']);
        });

        // Dictionary 9.10: cot generated chan 2 request dong thoi cung dat is_primary=true cho
        // cung 1 job (UNIQUE tren primary_flag_job_id, NULL khi is_primary=false — MariaDB coi
        // moi NULL la gia tri rieng biet nen khong chan nhieu hang is_primary=false).
        DB::statement(
            'ALTER TABLE job_locations ADD COLUMN primary_flag_job_id BIGINT UNSIGNED '.
            "GENERATED ALWAYS AS (IF(is_primary, job_id, NULL)) STORED UNIQUE"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('job_locations');
    }
};
