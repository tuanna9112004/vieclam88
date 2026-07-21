<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_duplicate_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
            $table->foreignId('candidate_id')->constrained('candidates')->restrictOnDelete();
            $table->foreignId('suspected_candidate_id')->constrained('candidates')->restrictOnDelete();
            // ADR-055/062: enum phu chua chot dung varchar + PHP backed enum
            // (App\Enums\CandidateDuplicateReviewReason).
            $table->string('reason_code', 30);
            // App\Enums\CandidateDuplicateReviewStatus.
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 255)->nullable();
            $table->timestamps();

            $table->index('application_id');
            $table->index('candidate_id');
            $table->index('suspected_candidate_id');
            $table->index('status');
        });

        // Dictionary 9.28 (ADR-062): chan 2 review pending trung cung cap
        // (candidate_id, suspected_candidate_id, reason_code) — cung pattern
        // job_locations.primary_flag_job_id.
        DB::statement(
            'ALTER TABLE candidate_duplicate_reviews ADD COLUMN pending_pair_key VARCHAR(80) '.
            "GENERATED ALWAYS AS (IF(status='pending', CONCAT(candidate_id,'-',suspected_candidate_id,'-',reason_code), NULL)) STORED UNIQUE"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_duplicate_reviews');
    }
};
