<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()
                ->constrained('administrative_units')->restrictOnDelete();
            $table->string('official_code', 20)->nullable()->unique();
            $table->string('name', 150);
            $table->string('slug', 170);
            $table->enum('type', ['province', 'city', 'commune', 'ward', 'special_zone', 'legacy_district']);
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->unique(['parent_id', 'slug']);
            $table->index('type');
            $table->index('is_active');
        });

        // ADR-065: generated column chặn trùng slug ở cấp root, vì MariaDB coi mỗi NULL
        // trong UNIQUE(parent_id, slug) là giá trị riêng biệt nên không tự chặn được.
        DB::statement(
            "ALTER TABLE administrative_units
                ADD COLUMN root_slug_key VARCHAR(170)
                    GENERATED ALWAYS AS (IF(parent_id IS NULL, slug, NULL)) STORED,
                ADD UNIQUE KEY administrative_units_root_slug_key_unique (root_slug_key)"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_units');
    }
};
