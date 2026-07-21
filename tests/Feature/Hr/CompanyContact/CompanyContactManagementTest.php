<?php

namespace Tests\Feature\Hr\CompanyContact;

use App\Models\Company;
use App\Models\CompanyContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyContactManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_contact_index(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $this->actingAs($admin)->get(route('hr.company-contacts.index', $company))->assertOk();
    }

    public function test_staff_can_view_contact_index(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($staff)->get(route('hr.company-contacts.index', $company))->assertOk();
    }

    public function test_guest_is_redirected_from_contact_index(): void
    {
        $company = Company::factory()->create();

        $this->get(route('hr.company-contacts.index', $company))->assertRedirect(route('hr.login'));
    }

    public function test_staff_can_create_contact(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($staff)->post(route('hr.company-contacts.store', $company), [
            'name' => 'Nguyễn Văn A',
            'phone' => '0912345678',
        ]);

        $response->assertRedirect(route('hr.company-contacts.index', $company));
        $this->assertDatabaseHas('company_contacts', [
            'company_id' => $company->id,
            'name' => 'Nguyễn Văn A',
            'phone' => '0912345678',
        ]);
    }

    public function test_creating_contact_requires_name(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($staff)->post(route('hr.company-contacts.store', $company), []);

        $response->assertSessionHasErrors('name');
    }

    public function test_new_contact_defaults_to_not_public_and_not_primary(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($staff)->post(route('hr.company-contacts.store', $company), [
            'name' => 'Nguyễn Văn B',
        ]);

        $contact = CompanyContact::where('name', 'Nguyễn Văn B')->firstOrFail();

        $this->assertFalse($contact->is_public);
        $this->assertFalse($contact->is_primary);
    }

    public function test_admin_can_update_contact(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $contact = CompanyContact::factory()->create(['company_id' => $company->id, 'name' => 'Ten cu']);

        $response = $this->actingAs($admin)->put(route('hr.company-contacts.update', [$company, $contact]), [
            'name' => 'Ten moi',
        ]);

        $response->assertRedirect(route('hr.company-contacts.index', $company));
        $this->assertSame('Ten moi', $contact->fresh()->name);
    }

    public function test_staff_cannot_delete_contact(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();
        $contact = CompanyContact::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($staff)->delete(route('hr.company-contacts.destroy', [$company, $contact]));

        $response->assertForbidden();
        $this->assertDatabaseHas('company_contacts', ['id' => $contact->id, 'deleted_at' => null]);
    }

    public function test_staff_cannot_restore_contact(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();
        $contact = CompanyContact::factory()->create(['company_id' => $company->id]);
        $contact->delete();

        $response = $this->actingAs($staff)->post(route('hr.company-contacts.restore', [$company, $contact]));

        $response->assertForbidden();
        $this->assertSoftDeleted('company_contacts', ['id' => $contact->id]);
    }

    public function test_admin_can_delete_and_restore_contact(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $contact = CompanyContact::factory()->create(['company_id' => $company->id]);

        $this->actingAs($admin)->delete(route('hr.company-contacts.destroy', [$company, $contact]))
            ->assertRedirect(route('hr.company-contacts.index', $company));
        $this->assertSoftDeleted('company_contacts', ['id' => $contact->id]);

        $this->actingAs($admin)->post(route('hr.company-contacts.restore', [$company, $contact]))
            ->assertRedirect(route('hr.company-contacts.index', $company));
        $this->assertDatabaseHas('company_contacts', ['id' => $contact->id, 'deleted_at' => null]);
    }

    public function test_setting_primary_unsets_previous_primary_contact(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $oldPrimary = CompanyContact::factory()->create(['company_id' => $company->id, 'is_primary' => true]);
        $newContact = CompanyContact::factory()->create(['company_id' => $company->id, 'is_primary' => false]);

        $response = $this->actingAs($admin)->put(route('hr.company-contacts.update', [$company, $newContact]), [
            'name' => $newContact->name,
            'is_primary' => '1',
        ]);

        $response->assertRedirect(route('hr.company-contacts.index', $company));
        $this->assertFalse($oldPrimary->fresh()->is_primary);
        $this->assertTrue($newContact->fresh()->is_primary);
    }

    public function test_setting_primary_does_not_affect_other_companies(): void
    {
        $admin = User::factory()->admin()->create();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $primaryOfB = CompanyContact::factory()->create(['company_id' => $companyB->id, 'is_primary' => true]);
        $newContactOfA = CompanyContact::factory()->create(['company_id' => $companyA->id, 'is_primary' => false]);

        $this->actingAs($admin)->put(route('hr.company-contacts.update', [$companyA, $newContactOfA]), [
            'name' => $newContactOfA->name,
            'is_primary' => '1',
        ]);

        $this->assertTrue($primaryOfB->fresh()->is_primary);
    }

    public function test_contact_from_different_company_returns_404_on_update(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $contact = CompanyContact::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->actingAs($admin)->put(route('hr.company-contacts.update', [$company, $contact]), [
            'name' => 'Bi doi ten sai',
        ]);

        $response->assertNotFound();
    }

    public function test_status_only_accepts_defined_values(): void
    {
        $staff = User::factory()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($staff)->post(route('hr.company-contacts.store', $company), [
            'name' => 'Nguyễn Văn C',
            'status' => 'archived',
        ]);

        $response->assertSessionHasErrors('status');
    }
}
