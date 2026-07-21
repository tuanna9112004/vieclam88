<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->restrictOnDelete();
            $table->enum('from_status', ['draft', 'published', 'paused', 'closed'])->nullable();
            $table->enum('to_status', ['draft', 'published', 'paused', 'closed']);
            $table->string('reason', 255)->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index('job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_status_histories');
    }
};
