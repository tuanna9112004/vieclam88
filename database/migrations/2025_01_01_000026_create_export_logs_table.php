<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exported_by')->constrained('users')->restrictOnDelete();
            $table->string('export_type', 50);
            $table->json('filters')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->string('file_name', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_logs');
    }
};
