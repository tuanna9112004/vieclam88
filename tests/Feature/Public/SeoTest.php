<?php

namespace Tests\Feature\Public;

use App\Models\AdministrativeUnit;
use App\Models\Branch;
use App\Models\CompanyLocation;
use App\Models\Job;
use App\Models\JobLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SeoTest extends TestCase
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

    public function test_sitemap_returns_xml_with_home_and_job_list_urls(): void
    {
        $response = $this->get(route('sitemap'))->assertOk();

        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<urlset', false);
        $response->assertSee('<loc>'.route('home').'</loc>', false);
        $response->assertSee('<loc>'.route('jobs.index').'</loc>', false);
    }

    public function test_sitemap_only_includes_currently_active_jobs(): void
    {
        $published = $this->createJob(['title' => 'Job sitemap published']);
        $draft = $this->createJob(['status' => 'draft']);
        $paused = $this->createJob(['status' => 'paused']);
        $closed = $this->createJob(['status' => 'closed']);
        $expired = $this->createJob(['expires_at' => now()->subDay()]);
        $deleted = $this->createJob();
        $deleted->delete();

        $response = $this->get(route('sitemap'))->assertOk();

        $response->assertSee('<loc>'.route('jobs.show', $published->slug).'</loc>', false);
        $response->assertDontSee(route('jobs.show', $draft->slug), false);
        $response->assertDontSee(route('jobs.show', $paused->slug), false);
        $response->assertDontSee(route('jobs.show', $closed->slug), false);
        $response->assertDontSee(route('jobs.show', $expired->slug), false);
        $response->assertDontSee(route('jobs.show', $deleted->slug), false);
    }

    public function test_sitemap_never_contains_hr_urls(): void
    {
        $response = $this->get(route('sitemap'))->assertOk();

        $response->assertDontSee('/hr', false);
    }

    public function test_home_page_has_canonical_tag(): void
    {
        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('<link rel="canonical" href="'.route('home').'">', false);
    }

    public function test_job_list_page_has_canonical_tag(): void
    {
        $response = $this->get(route('jobs.index'))->assertOk();

        $response->assertSee('<link rel="canonical" href="'.route('jobs.index').'">', false);
    }

    public function test_job_show_page_has_canonical_tag_for_every_status(): void
    {
        foreach (['published', 'paused', 'closed'] as $status) {
            $job = $this->createJob(['status' => $status]);

            $this->get(route('jobs.show', $job->slug))
                ->assertOk()
                ->assertSee('<link rel="canonical" href="'.route('jobs.show', $job->slug).'">', false);
        }

        $expired = $this->createJob(['expires_at' => now()->subDay()]);
        $this->get(route('jobs.show', $expired->slug))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('jobs.show', $expired->slug).'">', false);
    }

    public function test_hr_login_page_has_noindex_meta(): void
    {
        $response = $this->get(route('hr.login'))->assertOk();

        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_robots_txt_disallows_hr(): void
    {
        $content = File::get(public_path('robots.txt'));

        $this->assertStringContainsString('Disallow: /hr', $content);
    }
}
