<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_work_shifts', function (Blueprint $table) {
            $table->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
            $table->foreignId('work_shift_id')->constrained('work_shifts')->restrictOnDelete();
            $table->string('description', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->primary(['job_id', 'work_shift_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_work_shifts');
    }
};
