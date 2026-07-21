<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->restrictOnDelete();
            $table->foreignId('verified_by')->constrained('users')->restrictOnDelete();
            $table->enum('result', ['still_open', 'paused', 'closed', 'needs_review']);
            $table->string('note', 255)->nullable();
            $table->timestamp('verified_at');
            $table->timestamp('created_at')->nullable();

            $table->index('job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_verifications');
    }
};
