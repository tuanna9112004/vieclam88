<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industrial_parks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('administrative_unit_id')
                ->constrained('administrative_units')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('slug', 170);
            $table->string('official_name', 200)->nullable();
            $table->string('address_detail', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['administrative_unit_id', 'slug']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industrial_parks');
    }
};
