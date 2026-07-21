<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            // ADR-055: enum phu chua chot — cot DB la varchar(20), rang buoc gia tri o tang
            // ung dung qua backed enum App\Enums\SettingType, khong dung DB enum().
            $table->string('type', 20)->default('string');
            $table->string('group_name', 50)->default('general');
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index('group_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
