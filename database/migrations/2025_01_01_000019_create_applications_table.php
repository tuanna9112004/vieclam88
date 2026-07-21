<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('candidate_id')->constrained('candidates')->restrictOnDelete();
            $table->foreignId('job_id')->constrained('jobs')->restrictOnDelete();
            $table->foreignId('source_id')->nullable()->constrained('recruitment_sources')->nullOnDelete();
            $table->foreignId('owner_branch_id')->constrained('branches')->restrictOnDelete();
            $table->enum('stage', [
                'new', 'contacting', 'consulted', 'interview_scheduled', 'interviewed',
                'waiting_start', 'started', 'closed',
            ])->default('new');
            $table->timestamp('stage_changed_at')->useCurrent();
            $table->enum('close_reason', [
                'unreachable', 'candidate_cancelled', 'employer_cancelled', 'unsuitable',
                'duplicate', 'job_closed', 'other',
            ])->nullable();
            $table->unsignedInteger('workflow_cycle')->default(1);
            $table->timestamp('workflow_cycle_started_at')->useCurrent();
            $table->timestamp('reopened_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('submission_token', 64)->unique();
            $table->boolean('needs_duplicate_review')->default(false);
            $table->timestamp('duplicate_reviewed_at')->nullable();
            $table->foreignId('duplicate_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_reapplied_at')->nullable();
            $table->string('submitted_full_name', 150);
            $table->string('submitted_phone', 20);
            $table->string('submitted_phone_normalized', 20);
            $table->json('submission_snapshot');
            $table->json('job_snapshot');
            $table->string('source_detail', 255)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('landing_url', 500)->nullable();
            $table->string('consent_version', 20);
            $table->string('consent_text_hash', 64);
            $table->timestamp('consented_at');
            $table->string('consent_ip', 45)->nullable();
            $table->string('consent_user_agent', 255)->nullable();
            $table->date('expected_start_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['candidate_id', 'job_id']);
            $table->index('stage');
            $table->index('workflow_cycle');
            $table->index('needs_duplicate_review');
            $table->index('submitted_phone_normalized');
            $table->index('created_at');
            $table->index(['stage', 'created_at']);
            $table->index(['job_id', 'stage', 'created_at']);
            $table->index(['source_id', 'created_at']);
            $table->index(['candidate_id', 'created_at']);
            $table->index(['owner_branch_id', 'stage', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
