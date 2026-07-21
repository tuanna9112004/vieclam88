<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->string('full_name', 150);
            $table->string('full_name_normalized', 150);
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->foreignId('current_administrative_unit_id')->nullable()
                ->constrained('administrative_units')->restrictOnDelete();
            $table->string('address_detail', 255)->nullable();
            $table->string('education_level', 100)->nullable();
            $table->text('experience_summary')->nullable();
            $table->string('preferred_shift', 50)->nullable();
            $table->date('available_from')->nullable();
            $table->enum('status', ['active', 'merged', 'anonymized'])->default('active');
            $table->foreignId('merged_into_candidate_id')->nullable()
                ->constrained('candidates')->nullOnDelete();
            $table->timestamp('merged_at')->nullable();
            $table->foreignId('merged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('merge_reason', 255)->nullable();
            $table->timestamp('anonymized_at')->nullable();
            $table->foreignId('anonymized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('full_name_normalized');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
