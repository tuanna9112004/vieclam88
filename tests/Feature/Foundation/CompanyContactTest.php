<?php

namespace Tests\Feature\Foundation;

use App\Enums\CompanyContactStatus;
use App\Models\Company;
use App\Models\CompanyContact;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        CompanyContact::factory()->create(['company_id' => null]);
    }

    public function test_deleting_company_referenced_by_contact_is_restricted(): void
    {
        // Company::delete() la soft delete (khong cham FK) — dung forceDelete() de kiem tra
        // dung rang buoc RESTRICT o tang DB.
        $company = Company::factory()->create();
        CompanyContact::factory()->create(['company_id' => $company->id]);

        $this->expectException(QueryException::class);

        $company->forceDelete();
    }

    public function test_name_is_required(): void
    {
        $this->expectException(QueryException::class);

        CompanyContact::factory()->create(['name' => null]);
    }

    public function test_optional_fields_are_nullable(): void
    {
        $contact = CompanyContact::factory()->create([
            'position' => null,
            'phone' => null,
            'phone_normalized' => null,
            'zalo' => null,
            'email' => null,
        ]);

        $this->assertDatabaseHas('company_contacts', [
            'id' => $contact->id,
            'position' => null,
            'phone' => null,
            'zalo' => null,
            'email' => null,
        ]);
    }

    public function test_is_primary_and_is_public_default_to_false(): void
    {
        // Insert thẳng qua query builder, không qua factory, để xác nhận DB tự điền default
        // khi cột không được truyền trong câu insert.
        $company = Company::factory()->create();

        $id = DB::table('company_contacts')->insertGetId([
            'company_id' => $company->id,
            'name' => 'Nguyễn Văn A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('company_contacts', [
            'id' => $id,
            'is_primary' => false,
            'is_public' => false,
            'status' => 'active',
        ]);
    }

    public function test_status_casts_to_backed_enum(): void
    {
        $contact = CompanyContact::factory()->create(['status' => 'inactive']);

        $this->assertSame(CompanyContactStatus::Inactive, $contact->fresh()->status);
    }

    public function test_soft_delete_keeps_contact_row(): void
    {
        $contact = CompanyContact::factory()->create();

        $contact->delete();

        $this->assertSoftDeleted('company_contacts', ['id' => $contact->id]);
        $this->assertDatabaseHas('company_contacts', ['id' => $contact->id]);
    }

    public function test_restoring_a_soft_deleted_contact(): void
    {
        $contact = CompanyContact::factory()->create();
        $contact->delete();

        $contact->restore();

        $this->assertDatabaseHas('company_contacts', ['id' => $contact->id, 'deleted_at' => null]);
    }

    public function test_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $contact = CompanyContact::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($contact->company->is($company));
    }
}
