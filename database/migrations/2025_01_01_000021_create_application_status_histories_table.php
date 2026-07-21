<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
            $table->enum('from_stage', [
                'new', 'contacting', 'consulted', 'interview_scheduled', 'interviewed',
                'waiting_start', 'started', 'closed',
            ])->nullable();
            $table->enum('to_stage', [
                'new', 'contacting', 'consulted', 'interview_scheduled', 'interviewed',
                'waiting_start', 'started', 'closed',
            ]);
            $table->enum('close_reason', [
                'unreachable', 'candidate_cancelled', 'employer_cancelled', 'unsuitable',
                'duplicate', 'job_closed', 'other',
            ])->nullable();
            $table->unsignedInteger('workflow_cycle');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('actor_type', ['user', 'system'])->default('user');
            $table->string('note', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('application_id');
            $table->index('workflow_cycle');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_histories');
    }
};
