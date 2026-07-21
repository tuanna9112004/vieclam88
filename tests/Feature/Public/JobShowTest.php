<?php

namespace Tests\Feature\Public;

use App\Models\AdministrativeUnit;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyContact;
use App\Models\CompanyLocation;
use App\Models\Job;
use App\Models\JobLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobShowTest extends TestCase
{
    use RefreshDatabase;

    private AdministrativeUnit $unit;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // vi_VN Faker Address::city() chi co 5 gia tri co dinh — dung chung 1 don vi hanh
        // chinh/1 Branch mac dinh de tranh "Maximum retries" khi test tao nhieu ban ghi.
        $this->unit = AdministrativeUnit::factory()->create();
        $this->branch = Branch::factory()->create([
            'status' => 'active',
            'phone' => '0912345678',
            'zalo' => '0912345678',
            'administrative_unit_id' => $this->unit->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createJob(array $overrides = []): Job
    {
        $job = Job::factory()->create(array_merge([
            'status' => 'published',
            'owner_branch_id' => $this->branch->id,
        ], $overrides));

        $companyLocation = CompanyLocation::factory()->create([
            'company_id' => $job->company_id,
            'administrative_unit_id' => $this->unit->id,
        ]);

        JobLocation::factory()->create([
            'job_id' => $job->id,
            'company_location_id' => $companyLocation->id,
            'is_primary' => true,
        ]);

        return $job->fresh();
    }

    public function test_draft_job_has_no_public_detail_page(): void
    {
        $job = $this->createJob(['status' => 'draft']);

        $this->get(route('jobs.show', $job->slug))->assertNotFound();
    }

    public function test_soft_deleted_job_returns_404(): void
    {
        $job = $this->createJob();
        $job->delete();

        $this->get(route('jobs.show', $job->slug))->assertNotFound();
    }

    public function test_published_active_job_shows_ok_without_status_banner(): void
    {
        $job = $this->createJob(['title' => 'Cong nhan dang tuyen']);

        $response = $this->get(route('jobs.show', $job->slug))->assertOk();

        $response->assertSee($job->title);
        $response->assertSee('Gọi '.$this->branch->phone);
        $response->assertSee('Nhắn Zalo');
        $response->assertDontSee('Tạm ngừng tuyển');
        $response->assertDontSee('Đã ngừng tuyển');
        $response->assertDontSee('Đã hết hạn tuyển');
        $response->assertSee('application/ld+json', false);
    }

    public function test_paused_job_keeps_url_with_status_banner_and_cta(): void
    {
        $job = $this->createJob(['status' => 'paused', 'title' => 'Cong nhan tam dung']);

        $response = $this->get(route('jobs.show', $job->slug))->assertOk();

        $response->assertSee($job->title);
        $response->assertSee('Tạm ngừng tuyển');
        $response->assertSee('Gọi '.$this->branch->phone);
        $response->assertDontSee('application/ld+json', false);
    }

    public function test_closed_job_keeps_url_with_status_banner_and_cta(): void
    {
        $job = $this->createJob(['status' => 'closed', 'title' => 'Cong nhan da dong']);

        $response = $this->get(route('jobs.show', $job->slug))->assertOk();

        $response->assertSee($job->title);
        $response->assertSee('Đã ngừng tuyển');
        $response->assertSee('Gọi '.$this->branch->phone);
        $response->assertDontSee('application/ld+json', false);
    }

    public function test_expired_published_job_keeps_url_with_expired_banner(): void
    {
        $job = $this->createJob([
            'status' => 'published',
            'expires_at' => now()->subDay(),
            'title' => 'Cong nhan het han',
        ]);

        $response = $this->get(route('jobs.show', $job->slug))->assertOk();

        $response->assertSee($job->title);
        $response->assertSee('Đã hết hạn tuyển');
        $response->assertSee('Gọi '.$this->branch->phone);
        $response->assertDontSee('application/ld+json', false);
    }

    public function test_no_apply_button_is_rendered_for_any_status(): void
    {
        foreach (['published', 'paused', 'closed'] as $status) {
            $job = $this->createJob(['status' => $status, 'title' => 'Job trang thai '.$status]);

            $this->get(route('jobs.show', $job->slug))
                ->assertOk()
                ->assertDontSee('Ứng tuyển ngay');
        }
    }

    public function test_company_contact_hidden_when_not_public(): void
    {
        $contact = CompanyContact::factory()->create(['is_public' => false, 'status' => 'active']);
        $job = $this->createJob(['company_id' => $contact->company_id, 'company_contact_id' => $contact->id]);

        $this->get(route('jobs.show', $job->slug))
            ->assertOk()
            ->assertDontSee($contact->name);
    }

    public function test_company_contact_hidden_when_inactive_even_if_public(): void
    {
        $contact = CompanyContact::factory()->create(['is_public' => true, 'status' => 'inactive']);
        $job = $this->createJob(['company_id' => $contact->company_id, 'company_contact_id' => $contact->id]);

        $this->get(route('jobs.show', $job->slug))
            ->assertOk()
            ->assertDontSee($contact->name);
    }

    public function test_company_contact_hidden_when_belongs_to_different_company(): void
    {
        $contact = CompanyContact::factory()->create(['is_public' => true, 'status' => 'active']);
        $otherCompany = Company::factory()->create();
        $job = $this->createJob(['company_id' => $otherCompany->id, 'company_contact_id' => $contact->id]);

        $this->get(route('jobs.show', $job->slug))
            ->assertOk()
            ->assertDontSee($contact->name);
    }

    public function test_company_contact_shown_when_public_active_and_matching_company(): void
    {
        $contact = CompanyContact::factory()->create(['is_public' => true, 'status' => 'active', 'name' => 'Nguyen Van A']);
        $job = $this->createJob(['company_id' => $contact->company_id, 'company_contact_id' => $contact->id]);

        $this->get(route('jobs.show', $job->slug))
            ->assertOk()
            ->assertSee('Nguyen Van A');
    }

    public function test_related_jobs_only_include_currently_active_jobs_same_company_or_industry(): void
    {
        $company = Company::factory()->create(['industry' => 'may mac']);
        $job = $this->createJob(['company_id' => $company->id, 'title' => 'Job chinh']);

        $sameCompanyActive = $this->createJob(['company_id' => $company->id, 'title' => 'Job cung cong ty dang tuyen']);

        $sameCompanyPaused = $this->createJob(['company_id' => $company->id, 'status' => 'paused', 'title' => 'Job cung cong ty tam dung']);

        $sameIndustryOtherCompany = $this->createJob([
            'company_id' => Company::factory()->create(['industry' => 'may mac'])->id,
            'title' => 'Job cung nganh',
        ]);

        $unrelated = $this->createJob([
            'company_id' => Company::factory()->create(['industry' => 'xay dung'])->id,
            'title' => 'Job khong lien quan',
        ]);

        $response = $this->get(route('jobs.show', $job->slug))->assertOk();

        $response->assertSee($sameCompanyActive->title);
        $response->assertSee($sameIndustryOtherCompany->title);
        $response->assertDontSee($sameCompanyPaused->title);
        $response->assertDontSee($unrelated->title);
    }
}
