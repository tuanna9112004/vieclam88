<?php

namespace Tests\Feature\Hr\Company;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_company_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('hr.companies.index'))->assertOk();
    }

    public function test_staff_can_view_company_index(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('hr.companies.index'))->assertOk();
    }

    public function test_guest_is_redirected_from_company_index(): void
    {
        $this->get(route('hr.companies.index'))->assertRedirect(route('hr.login'));
    }

    public function test_staff_can_create_company_with_only_name(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->post(route('hr.companies.store'), [
            'name' => 'Công ty TNHH ABC',
        ]);

        $response->assertRedirect(route('hr.companies.index'));
        $this->assertDatabaseHas('companies', [
            'name' => 'Công ty TNHH ABC',
            'status' => 'active',
            'created_by' => $staff->id,
        ]);
    }

    public function test_creating_company_requires_name(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->post(route('hr.companies.store'), []);

        $response->assertSessionHasErrors('name');
    }

    public function test_creating_company_generates_slug_and_public_id_server_side(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->post(route('hr.companies.store'), [
            'name' => 'Công ty Kiểm Thử',
            'slug' => 'client-supplied-slug',
            'public_id' => 'client-supplied-id',
            'status' => 'hidden',
            'created_by' => 999,
        ]);

        $company = Company::where('name', 'Công ty Kiểm Thử')->firstOrFail();

        $this->assertNotSame('client-supplied-slug', $company->slug);
        $this->assertNotSame('client-supplied-id', $company->public_id);
        $this->assertSame(26, strlen($company->public_id));
        $this->assertSame('active', $company->status);
        $this->assertSame($staff->id, $company->created_by);
    }

    public function test_admin_can_update_company(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create(['name' => 'Ten cu']);

        $response = $this->actingAs($admin)->put(route('hr.companies.update', $company), [
            'name' => 'Ten moi',
        ]);

        $response->assertRedirect(route('hr.companies.index'));
        $this->assertSame('Ten moi', $company->fresh()->name);
        $this->assertSame($admin->id, $company->fresh()->updated_by);
    }

    public function test_updating_company_regenerates_slug_when_name_changes(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create(['name' => 'Ten cu', 'slug' => 'ten-cu']);

        $this->actingAs($admin)->put(route('hr.companies.update', $company), [
            'name' => 'Ten hoan toan moi',
        ]);

        $this->assertSame('ten-hoan-toan-moi', $company->fresh()->slug);
    }

    public function test_staff_cannot_delete_company(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($staff)->delete(route('hr.companies.destroy', $company));

        $response->assertForbidden();
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'deleted_at' => null]);
    }

    public function test_staff_cannot_restore_company(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();
        $company->delete();

        $response = $this->actingAs($staff)->post(route('hr.companies.restore', $company));

        $response->assertForbidden();
        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }

    public function test_admin_can_delete_and_restore_company(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $this->actingAs($admin)->delete(route('hr.companies.destroy', $company))
            ->assertRedirect(route('hr.companies.index'));
        $this->assertSoftDeleted('companies', ['id' => $company->id]);

        $this->actingAs($admin)->post(route('hr.companies.restore', $company))
            ->assertRedirect(route('hr.companies.index'));
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'deleted_at' => null]);
    }

    public function test_creating_duplicate_name_company_shows_warning_but_still_creates(): void
    {
        $admin = User::factory()->admin()->create();
        Company::factory()->create(['name' => 'Công ty Trùng Tên']);

        $response = $this->actingAs($admin)->post(route('hr.companies.store'), [
            'name' => 'Công ty Trùng Tên',
        ]);

        $response->assertRedirect(route('hr.companies.index'));
        $response->assertSessionHas('duplicate_warning');
        $this->assertSame(2, Company::where('name', 'Công ty Trùng Tên')->count());
    }

    public function test_creating_unique_name_company_shows_no_duplicate_warning(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('hr.companies.store'), [
            'name' => 'Công ty Không Trùng',
        ]);

        $response->assertRedirect(route('hr.companies.index'));
        $this->assertNull(session('duplicate_warning'));
    }

    public function test_duplicate_warning_is_case_insensitive_and_ignores_extra_whitespace(): void
    {
        $admin = User::factory()->admin()->create();
        Company::factory()->create(['name' => 'Công Ty  ABC']);

        $response = $this->actingAs($admin)->post(route('hr.companies.store'), [
            'name' => 'công ty abc',
        ]);

        $response->assertSessionHas('duplicate_warning');
    }
}
