<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->enum('role', ['staff', 'admin'])->index();
            // Bắt buộc khi role=staff — chốt ở Form Request/Service (Dictionary 9.1),
            // không ép NOT NULL ở DB vì admin không thuộc cơ sở nào.
            $table->foreignId('branch_id')->nullable()
                ->constrained('branches')->nullOnDelete();
            $table->string('name', 150);
            $table->string('email', 191)->unique();
            $table->string('password');
            $table->enum('status', ['active', 'locked'])->default('active')->index();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
