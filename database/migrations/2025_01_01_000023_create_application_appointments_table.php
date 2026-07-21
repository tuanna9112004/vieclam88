<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
            $table->enum('type', ['callback', 'interview']);
            $table->timestamp('scheduled_at');
            $table->string('location_detail', 255)->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->string('outcome', 255)->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('workflow_cycle');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('application_id');
            $table->index('type');
            $table->index('scheduled_at');
            $table->index('status');
            $table->index('workflow_cycle');
            $table->index(['application_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_appointments');
    }
};
