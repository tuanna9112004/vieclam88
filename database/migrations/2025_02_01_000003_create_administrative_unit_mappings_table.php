<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_unit_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('administrative_unit_id')->unique()
                ->constrained('administrative_units')->restrictOnDelete();
            $table->foreignId('province_id')->nullable()
                ->constrained('provinces')->restrictOnDelete();
            $table->foreignId('ward_id')->nullable()
                ->constrained('wards')->restrictOnDelete();
            $table->enum('status', ['mapped', 'ambiguous', 'missing', 'invalid_parent']);
            $table->text('reason')->nullable();
            $table->timestamp('mapped_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_unit_mappings');
    }
};
