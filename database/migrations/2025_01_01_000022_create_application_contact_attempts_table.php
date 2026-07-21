<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_contact_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
            $table->foreignId('contacted_by')->constrained('users')->restrictOnDelete();
            $table->enum('channel', ['phone', 'zalo', 'sms', 'email', 'other']);
            $table->enum('result', [
                'reached', 'no_answer', 'busy', 'wrong_number', 'consulted', 'callback_requested',
                'interview_agreed', 'candidate_refused', 'unsuitable', 'message_sent', 'other',
            ]);
            $table->unsignedInteger('workflow_cycle');
            $table->timestamp('contacted_at')->useCurrent();
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('application_id');
            $table->index('workflow_cycle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_contact_attempts');
    }
};
