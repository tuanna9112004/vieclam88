<?php

namespace Tests\Feature\Public;

use App\Models\AdministrativeUnit;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyContact;
use App\Models\CompanyLocation;
use App\Models\IndustrialPark;
use App\Models\Job;
use App\Models\JobLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CompanyIndustrialParkContactTest extends TestCase
{
    use RefreshDatabase;

    private AdministrativeUnit $unit;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = AdministrativeUnit::factory()->create(['is_active' => true]);
        $this->branch = Branch::factory()->create([
            'name' => 'Cơ sở công khai Bắc Ninh',
            'status' => 'active',
            'phone' => '0912345678',
            'zalo' => '0987654321',
            'administrative_unit_id' => $this->unit->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createJob(
        Company $company,
        array $overrides = [],
        ?IndustrialPark $industrialPark = null
    ): Job {
        $location = CompanyLocation::factory()->create([
            'company_id' => $company->id,
            'administrative_unit_id' => $this->unit->id,
            'industrial_park_id' => $industrialPark?->id,
            'status' => 'active',
        ]);

        $job = Job::factory()->create(array_merge([
            'company_id' => $company->id,
            'owner_branch_id' => $this->branch->id,
            'status' => 'published',
            'published_at' => now(),
        ], $overrides));

        JobLocation::factory()->create([
            'job_id' => $job->id,
            'company_location_id' => $location->id,
            'is_primary' => true,
        ]);

        return $job->fresh();
    }

    public function test_route_map_public_routes_are_registered(): void
    {
        foreach (['companies.index', 'companies.show', 'industrial-parks.show', 'contact.show'] as $routeName) {
            $this->assertTrue(Route::has($routeName), "Thiếu route $routeName.");
        }
    }

    public function test_company_index_only_lists_active_not_deleted_companies_with_basic_seo(): void
    {
        $active = Company::factory()->create(['name' => 'Công ty Active', 'status' => 'active']);
        $hidden = Company::factory()->create(['name' => 'Công ty Hidden', 'status' => 'hidden']);
        $deleted = Company::factory()->create(['name' => 'Công ty Deleted', 'status' => 'active']);
        $deleted->delete();
        $this->createJob($active, ['title' => 'Job công khai của Active']);

        $response = $this->get(route('companies.index'))->assertOk();

        $response->assertSee($active->name);
        $response->assertDontSee($hidden->name);
        $response->assertDontSee($deleted->name);
        $response->assertSee('1 việc đang tuyển');
        $response->assertSee('<link rel="canonical" href="'.route('companies.index').'">', false);
        $response->assertSee('<meta property="og:title"', false);
    }

    public function test_company_show_only_lists_effective_public_jobs_and_never_exposes_company_contacts(): void
    {
        $company = Company::factory()->create([
            'name' => 'Nhà máy Privacy',
            'status' => 'active',
        ]);
        $published = $this->createJob($company, ['title' => 'Job public còn hiệu lực']);
        $draft = $this->createJob($company, ['title' => 'Job draft nội bộ', 'status' => 'draft']);
        $paused = $this->createJob($company, ['title' => 'Job tạm dừng nội bộ', 'status' => 'paused']);
        $expired = $this->createJob($company, [
            'title' => 'Job đã hết hạn',
            'expires_at' => now()->subDay(),
        ]);
        $inactiveBranch = Branch::factory()->create([
            'status' => 'inactive',
            'administrative_unit_id' => $this->unit->id,
        ]);
        $inactiveBranchJob = $this->createJob($company, [
            'title' => 'Job của cơ sở đã dừng',
            'owner_branch_id' => $inactiveBranch->id,
        ]);
        $deleted = $this->createJob($company, ['title' => 'Job đã soft delete']);
        $deleted->delete();

        CompanyContact::factory()->create([
            'company_id' => $company->id,
            'name' => 'Đầu mối tuyệt mật',
            'phone' => '0900000999',
            'is_public' => true,
        ]);

        $response = $this->get(route('companies.show', $company->slug))->assertOk();

        $response->assertSee($published->title);
        $response->assertDontSee($draft->title);
        $response->assertDontSee($paused->title);
        $response->assertDontSee($expired->title);
        $response->assertDontSee($inactiveBranchJob->title);
        $response->assertDontSee($deleted->title);
        $response->assertDontSee('Đầu mối tuyệt mật');
        $response->assertDontSee('0900000999');
        $response->assertSee('<link rel="canonical" href="'.route('companies.show', $company->slug).'">', false);
    }

    public function test_hidden_or_deleted_company_detail_is_not_public(): void
    {
        $hidden = Company::factory()->create(['status' => 'hidden']);
        $deleted = Company::factory()->create(['status' => 'active']);
        $deleted->delete();

        $this->get(route('companies.show', $hidden->slug))->assertNotFound();
        $this->get(route('companies.show', $deleted->slug))->assertNotFound();
    }

    public function test_industrial_park_page_only_lists_matching_effective_public_jobs(): void
    {
        $park = IndustrialPark::factory()->create([
            'administrative_unit_id' => $this->unit->id,
            'name' => 'KCN Public',
            'is_active' => true,
        ]);
        $otherPark = IndustrialPark::factory()->create([
            'administrative_unit_id' => $this->unit->id,
            'name' => 'KCN Khác',
            'is_active' => true,
        ]);
        $company = Company::factory()->create(['status' => 'active']);

        $matching = $this->createJob($company, ['title' => 'Job đúng KCN'], $park);
        $other = $this->createJob($company, ['title' => 'Job KCN khác'], $otherPark);
        $draft = $this->createJob($company, ['title' => 'Job draft trong KCN', 'status' => 'draft'], $park);
        $expired = $this->createJob($company, [
            'title' => 'Job hết hạn trong KCN',
            'expires_at' => now()->subDay(),
        ], $park);
        $hiddenCompany = Company::factory()->create(['status' => 'hidden']);
        $hiddenCompanyJob = $this->createJob($hiddenCompany, ['title' => 'Job của công ty hidden'], $park);

        $response = $this->get(route('industrial-parks.show', $park->slug))->assertOk();

        $response->assertSee($matching->title);
        $response->assertDontSee($other->title);
        $response->assertDontSee($draft->title);
        $response->assertDontSee($expired->title);
        $response->assertDontSee($hiddenCompanyJob->title);
        $response->assertSee('<link rel="canonical" href="'.route('industrial-parks.show', $park->slug).'">', false);
    }

    public function test_inactive_park_or_park_under_inactive_unit_is_not_public(): void
    {
        $inactivePark = IndustrialPark::factory()->inactive()->create([
            'administrative_unit_id' => $this->unit->id,
        ]);
        $inactiveUnit = AdministrativeUnit::factory()->create(['is_active' => false]);
        $parkUnderInactiveUnit = IndustrialPark::factory()->create([
            'administrative_unit_id' => $inactiveUnit->id,
            'is_active' => true,
        ]);

        $this->get(route('industrial-parks.show', $inactivePark->slug))->assertNotFound();
        $this->get(route('industrial-parks.show', $parkUnderInactiveUnit->slug))->assertNotFound();
    }

    public function test_contact_page_is_static_uses_only_active_branch_ctas_and_does_not_expose_company_contacts(): void
    {
        $inactiveBranch = Branch::factory()->create([
            'name' => 'Cơ sở đã dừng',
            'phone' => '0900111222',
            'status' => 'inactive',
            'administrative_unit_id' => $this->unit->id,
        ]);
        CompanyContact::factory()->create([
            'name' => 'Liên hệ nội bộ không được lộ',
            'phone' => '0900999888',
            'is_public' => true,
        ]);

        $response = $this->get(route('contact.show'))->assertOk();

        $response->assertSee($this->branch->name);
        $response->assertSee('tel:'.$this->branch->phone, false);
        $response->assertSee('https://zalo.me/'.$this->branch->zalo, false);
        $response->assertDontSee($inactiveBranch->name);
        $response->assertDontSee('Liên hệ nội bộ không được lộ');
        $response->assertDontSee('0900999888');
        $response->assertDontSee('<form', false);
        $response->assertSee('<link rel="canonical" href="'.route('contact.show').'">', false);
    }

    public function test_sitemap_only_adds_active_company_and_industrial_park_pages(): void
    {
        $activeCompany = Company::factory()->create(['status' => 'active']);
        $hiddenCompany = Company::factory()->create(['status' => 'hidden']);
        $activePark = IndustrialPark::factory()->create([
            'administrative_unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        $inactivePark = IndustrialPark::factory()->inactive()->create([
            'administrative_unit_id' => $this->unit->id,
        ]);

        $response = $this->get(route('sitemap'))->assertOk();

        $response->assertSee(route('companies.index'), false);
        $response->assertSee(route('contact.show'), false);
        $response->assertSee(route('companies.show', $activeCompany->slug), false);
        $response->assertDontSee(route('companies.show', $hiddenCompany->slug), false);
        $response->assertSee(route('industrial-parks.show', $activePark->slug), false);
        $response->assertDontSee(route('industrial-parks.show', $inactivePark->slug), false);
    }
}
