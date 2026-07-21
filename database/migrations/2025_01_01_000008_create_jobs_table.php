<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('company_contact_id')->nullable()
                ->constrained('company_contacts')->nullOnDelete();
            $table->foreignId('owner_branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            // ADR-055: enum phụ chưa chốt dùng varchar + PHP backed enum (App\Enums\JobEmploymentType).
            $table->string('employment_type', 20)->default('full_time');
            $table->smallInteger('quantity')->unsigned()->nullable();
            $table->enum('gender_requirement', ['male', 'female', 'any'])->nullable();
            $table->tinyInteger('min_age')->unsigned()->nullable();
            $table->tinyInteger('max_age')->unsigned()->nullable();
            $table->string('education_requirement', 255)->nullable();
            $table->string('experience_requirement', 255)->nullable();
            $table->unsignedBigInteger('salary_min')->nullable();
            $table->unsignedBigInteger('salary_max')->nullable();
            $table->unsignedBigInteger('salary_base')->nullable();
            $table->enum('salary_period', ['month', 'day', 'hour', 'piece', 'negotiable'])->default('month');
            $table->string('currency', 3)->default('VND');
            $table->text('salary_description')->nullable();
            $table->text('job_description')->nullable();
            $table->text('requirements')->nullable();
            $table->text('benefits')->nullable();
            $table->text('application_documents')->nullable();
            $table->boolean('has_shuttle_bus')->default(false);
            $table->text('shuttle_bus_details')->nullable();
            $table->boolean('has_accommodation')->default(false);
            $table->text('accommodation_details')->nullable();
            $table->boolean('has_meal_support')->default(false);
            $table->text('meal_support_details')->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->enum('status', ['draft', 'published', 'paused', 'closed'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            // ADR-055: enum phụ chưa chốt dùng varchar + PHP backed enum (App\Enums\JobCloseReason).
            $table->string('close_reason', 30)->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_urgent');
            $table->index('expires_at');
            $table->index(['company_id', 'status']);
            $table->index(['owner_branch_id', 'status']);
        });

        // Dictionary 9.9: CHECK(min_age <= max_age) va CHECK(salary_min <= salary_max) khi ca hai
        // co gia tri. Blueprint chua co check() native trong ban Laravel nay, them qua raw SQL.
        DB::statement(
            'ALTER TABLE jobs ADD CONSTRAINT chk_jobs_age_range '.
            'CHECK (min_age IS NULL OR max_age IS NULL OR min_age <= max_age)'
        );
        DB::statement(
            'ALTER TABLE jobs ADD CONSTRAINT chk_jobs_salary_range '.
            'CHECK (salary_min IS NULL OR salary_max IS NULL OR salary_min <= salary_max)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
