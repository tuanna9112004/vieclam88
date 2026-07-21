<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_branch_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->restrictOnDelete();
            $table->foreignId('from_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('to_branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('reason', 255)->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index('job_id');
            $table->index('to_branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_branch_histories');
    }
};
