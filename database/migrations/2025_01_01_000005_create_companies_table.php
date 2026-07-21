<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->string('short_name', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->string('cover_path', 255)->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('website', 255)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->enum('status', ['active', 'hidden'])->default('active');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_verified');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
