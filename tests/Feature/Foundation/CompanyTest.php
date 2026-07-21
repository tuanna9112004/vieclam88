<?php

namespace Tests\Feature\Foundation;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_id_must_be_unique(): void
    {
        Company::factory()->create(['public_id' => 'DUP-PUBLIC-ID']);

        $this->expectException(QueryException::class);

        Company::factory()->create(['public_id' => 'DUP-PUBLIC-ID']);
    }

    public function test_slug_must_be_unique(): void
    {
        Company::factory()->create(['slug' => 'cong-ty-abc']);

        $this->expectException(QueryException::class);

        Company::factory()->create(['slug' => 'cong-ty-abc']);
    }

    public function test_name_is_required(): void
    {
        $this->expectException(QueryException::class);

        Company::factory()->create(['name' => null]);
    }

    public function test_created_by_is_required(): void
    {
        $this->expectException(QueryException::class);

        Company::factory()->create(['created_by' => null]);
    }

    public function test_status_only_accepts_defined_values(): void
    {
        $this->expectException(QueryException::class);

        Company::factory()->create(['status' => 'archived']);
    }

    public function test_status_defaults_to_active_when_not_specified(): void
    {
        // Insert thẳng qua query builder, không qua factory (factory luôn set 'status'), để
        // xác nhận DB tự điền default 'active' khi cột không được truyền trong câu insert.
        $admin = User::factory()->admin()->create();

        $id = DB::table('companies')->insertGetId([
            'public_id' => 'default-status-test',
            'name' => 'Công ty mặc định',
            'slug' => 'cong-ty-mac-dinh',
            'is_verified' => false,
            'created_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('companies', ['id' => $id, 'status' => 'active']);
    }

    public function test_is_verified_defaults_to_false(): void
    {
        $company = Company::factory()->create();

        $this->assertFalse($company->fresh()->is_verified);
    }

    public function test_deleting_creator_user_is_restricted(): void
    {
        $admin = User::factory()->admin()->create();
        Company::factory()->create(['created_by' => $admin->id]);

        $this->expectException(QueryException::class);

        $admin->delete();
    }

    public function test_deleting_updater_user_sets_updated_by_null(): void
    {
        $admin = User::factory()->admin()->create();
        $updater = User::factory()->admin()->create();
        $company = Company::factory()->create(['created_by' => $admin->id, 'updated_by' => $updater->id]);

        $updater->delete();

        $this->assertNull($company->fresh()->updated_by);
    }

    public function test_soft_delete_keeps_company_row(): void
    {
        $company = Company::factory()->create();

        $company->delete();

        $this->assertSoftDeleted('companies', ['id' => $company->id]);
        $this->assertDatabaseHas('companies', ['id' => $company->id]);
    }

    public function test_restoring_a_soft_deleted_company(): void
    {
        $company = Company::factory()->create();
        $company->delete();

        $company->restore();

        $this->assertDatabaseHas('companies', ['id' => $company->id, 'deleted_at' => null]);
    }
}
