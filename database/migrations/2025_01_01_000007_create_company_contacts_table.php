<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('position', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('phone_normalized', 20)->nullable()->index();
            $table->string('zalo', 20)->nullable();
            $table->string('email', 191)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_public')->default(false);
            // ADR-055: enum phụ chưa chốt dùng varchar + PHP backed enum (App\Enums\CompanyContactStatus),
            // không dùng DB enum() — đổi giá trị sau này không cần migration.
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_contacts');
    }
};
