<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->string('phone', 20)->nullable();
            $table->string('phone_normalized', 20)->nullable()->index();
            $table->string('zalo', 20)->nullable();
            $table->string('email', 191)->nullable();
            $table->foreignId('administrative_unit_id')
                ->constrained('administrative_units')->restrictOnDelete();
            $table->string('address_detail', 255)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
