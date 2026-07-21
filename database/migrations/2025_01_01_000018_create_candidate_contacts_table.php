<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->enum('type', ['phone', 'email', 'zalo', 'other']);
            $table->string('value', 191);
            $table->string('normalized_value', 191);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['candidate_id', 'type', 'normalized_value']);
            $table->index(['type', 'normalized_value']);
            $table->index(['candidate_id', 'is_primary']);
            $table->index(['candidate_id', 'is_active']);
        });

        // Dictionary 9.3 (ADR-064): chan 2 request dong thoi cung dat is_primary=true cho cung
        // 1 (candidate_id, type) — cung pattern job_locations.primary_flag_job_id.
        DB::statement(
            'ALTER TABLE candidate_contacts ADD COLUMN primary_flag_key VARCHAR(70) '.
            "GENERATED ALWAYS AS (IF(is_primary, CONCAT(candidate_id,'-',type), NULL)) STORED UNIQUE"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_contacts');
    }
};
