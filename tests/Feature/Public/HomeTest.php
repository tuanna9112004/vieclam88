<?php

namespace Tests\Feature\Public;

use App\Models\AdministrativeUnit;
use App\Models\Branch;
use App\Models\CompanyLocation;
use App\Models\Job;
use App\Models\JobLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomeTest extends TestCase
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
    private function createJob(array $overrides = [], ?AdministrativeUnit $unit = null): Job
    {
        $job = Job::factory()->create(array_merge([
            'status' => 'published',
            'owner_branch_id' => $this->branch->id,
        ], $overrides));

        $companyLocation = CompanyLocation::factory()->create([
            'company_id' => $job->company_id,
            'administrative_unit_id' => ($unit ?? $this->unit)->id,
        ]);

        JobLocation::factory()->create([
            'job_id' => $job->id,
            'company_location_id' => $companyLocation->id,
            'is_primary' => true,
        ]);

        return $job->fresh();
    }

    public function test_homepage_loads_ok_and_is_responsive_container(): void
    {
        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('Công việc mơ ước của bạn');
        $response->assertSee('viewport', false);
    }

    public function test_search_form_submits_to_job_list(): void
    {
        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('action="'.route('jobs.index').'"', false);
        $response->assertSee('name="q"', false);
    }

    public function test_featured_section_only_shows_urgent_published_active_jobs(): void
    {
        $urgent = $this->createJob(['is_urgent' => true, 'title' => 'Job khan cap']);
        $notUrgent = $this->createJob(['is_urgent' => false, 'title' => 'Job binh thuong']);
        $urgentButDraft = $this->createJob(['is_urgent' => true, 'status' => 'draft', 'title' => 'Job khan cap nhap']);
        $urgentButExpired = $this->createJob(['is_urgent' => true, 'expires_at' => now()->subDay(), 'title' => 'Job khan cap het han']);

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee($urgent->title);
        $response->assertDontSee($urgentButDraft->title);
        $response->assertDontSee($urgentButExpired->title);
    }

    public function test_newest_section_excludes_draft_paused_closed_expired_deleted(): void
    {
        $active = $this->createJob(['title' => 'Job moi nhat con hoat dong']);
        $draft = $this->createJob(['status' => 'draft', 'title' => 'Job nhap moi']);
        $paused = $this->createJob(['status' => 'paused', 'title' => 'Job tam dung moi']);
        $closed = $this->createJob(['status' => 'closed', 'title' => 'Job dong moi']);
        $deleted = $this->createJob(['title' => 'Job da xoa moi']);
        $deleted->delete();

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee($active->title);
        $response->assertDontSee($draft->title);
        $response->assertDontSee($paused->title);
        $response->assertDontSee($closed->title);
        $response->assertDontSee($deleted->title);
    }

    public function test_region_section_links_to_filtered_job_list(): void
    {
        $this->createJob(['title' => 'Job theo khu vuc']);

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee($this->unit->name);
        $response->assertSee('administrative_unit_id='.$this->unit->id, false);
    }

    public function test_region_section_excludes_unit_with_no_active_jobs(): void
    {
        // Don vi khong co Job nao van xuat hien trong dropdown tim kiem (danh sach khu vuc chung),
        // nhung khong duoc co card rieng trong "Viec lam theo khu vuc" (chi don vi co Job).
        $emptyUnit = AdministrativeUnit::factory()->create();

        $response = $this->get(route('home'))->assertOk();

        $response->assertDontSee(route('jobs.index', ['administrative_unit_id' => $emptyUnit->id]), false);
    }

    public function test_homepage_query_count_does_not_grow_with_number_of_jobs(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->createJob(['title' => 'Job home N+1 '.uniqid()]);
        }

        DB::enableQueryLog();
        $this->get(route('home'))->assertOk();
        $queriesForThree = count(DB::getQueryLog());
        DB::flushQueryLog();
        DB::disableQueryLog();

        for ($i = 0; $i < 9; $i++) {
            $this->createJob(['title' => 'Job home N+1 '.uniqid()]);
        }

        DB::enableQueryLog();
        $this->get(route('home'))->assertOk();
        $queriesForTwelve = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(
            $queriesForThree,
            $queriesForTwelve,
            'So luong query trang chu khong duoc tang theo so Job (N+1).'
        );
    }

    public function test_job_search_by_keyword_filters_job_list(): void
    {
        $match = $this->createJob(['title' => 'Cong nhan may Newwing']);
        $other = $this->createJob(['title' => 'Ky thuat vien Foxconn']);

        $response = $this->get(route('jobs.index', ['q' => 'Newwing']))->assertOk();

        $response->assertSee($match->title);
        $response->assertDontSee($other->title);
    }
}
