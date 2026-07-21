<?php

namespace Tests\Feature\Public;

use App\Models\AdministrativeUnit;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\IndustrialPark;
use App\Models\Job;
use App\Models\JobLocation;
use App\Models\JobWorkShift;
use App\Models\WorkShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JobListTest extends TestCase
{
    use RefreshDatabase;

    private AdministrativeUnit $unit;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // vi_VN Faker Address::city() chi co 5 gia tri co dinh — dung chung 1 don vi hanh
        // chinh/1 Branch mac dinh de tranh "Maximum retries" khi test tao nhieu Branch/CompanyLocation.
        $this->unit = AdministrativeUnit::factory()->create();
        $this->branch = Branch::factory()->create([
            'status' => 'active',
            'phone' => '0912345678',
            'zalo' => '0912345678',
            'administrative_unit_id' => $this->unit->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $jobOverrides
     */
    private function createListedJob(array $jobOverrides = [], ?CompanyLocation $companyLocation = null, ?WorkShift $workShift = null): Job
    {
        $job = Job::factory()->create(array_merge([
            'status' => 'published',
            'owner_branch_id' => Branch::factory()->create([
                'status' => 'active',
                'phone' => '0912345678',
                'zalo' => '0912345678',
                'administrative_unit_id' => $this->unit->id,
            ]),
        ], $jobOverrides));

        $companyLocation ??= CompanyLocation::factory()->create([
            'company_id' => $job->company_id,
            'administrative_unit_id' => null,
        ]);

        JobLocation::factory()->create([
            'job_id' => $job->id,
            'company_location_id' => $companyLocation->id,
            'is_primary' => true,
        ]);

        $workShift ??= WorkShift::factory()->create();

        JobWorkShift::factory()->create([
            'job_id' => $job->id,
            'work_shift_id' => $workShift->id,
        ]);

        return $job->fresh();
    }

    public function test_only_published_not_expired_not_deleted_jobs_are_listed(): void
    {
        $published = $this->createListedJob(['title' => 'Cong nhan published']);
        $draft = $this->createListedJob(['status' => 'draft', 'title' => 'Cong nhan draft']);
        $paused = $this->createListedJob(['status' => 'paused', 'title' => 'Cong nhan paused']);
        $closed = $this->createListedJob(['status' => 'closed', 'title' => 'Cong nhan closed']);
        $expired = $this->createListedJob(['status' => 'published', 'expires_at' => now()->subDay(), 'title' => 'Cong nhan expired']);
        $deleted = $this->createListedJob(['title' => 'Cong nhan deleted']);
        $deleted->delete();

        $response = $this->get(route('jobs.index'))->assertOk();

        $response->assertSee($published->title);
        $response->assertDontSee($draft->title);
        $response->assertDontSee($paused->title);
        $response->assertDontSee($closed->title);
        $response->assertDontSee($expired->title);
        $response->assertDontSee($deleted->title);
    }

    public function test_not_expired_includes_job_with_null_or_future_expires_at(): void
    {
        $noExpiry = $this->createListedJob(['title' => 'Khong het han']);
        $futureExpiry = $this->createListedJob(['expires_at' => now()->addMonth(), 'title' => 'Het han tuong lai']);

        $response = $this->get(route('jobs.index'))->assertOk();

        $response->assertSee($noExpiry->title);
        $response->assertSee($futureExpiry->title);
    }

    public function test_filters_by_industrial_park_only_matching_active_relation(): void
    {
        $park = IndustrialPark::factory()->create(['administrative_unit_id' => $this->unit->id]);
        $location = CompanyLocation::factory()->create(['industrial_park_id' => $park->id, 'administrative_unit_id' => null, 'status' => 'active']);
        $match = $this->createListedJob(['title' => 'Job trong KCN'], companyLocation: $location);

        $otherPark = IndustrialPark::factory()->create(['administrative_unit_id' => $this->unit->id]);
        $otherLocation = CompanyLocation::factory()->create(['industrial_park_id' => $otherPark->id, 'administrative_unit_id' => null, 'status' => 'active']);
        $other = $this->createListedJob(['title' => 'Job KCN khac'], companyLocation: $otherLocation);

        $response = $this->get(route('jobs.index', ['industrial_park_id' => $park->id]))->assertOk();

        $response->assertSee($match->title);
        $response->assertDontSee($other->title);
    }

    public function test_filter_excludes_job_when_related_industrial_park_is_inactive(): void
    {
        $park = IndustrialPark::factory()->inactive()->create(['administrative_unit_id' => $this->unit->id]);
        $location = CompanyLocation::factory()->create(['industrial_park_id' => $park->id, 'administrative_unit_id' => null, 'status' => 'active']);
        $job = $this->createListedJob(['title' => 'Job KCN ngung hoat dong'], companyLocation: $location);

        $response = $this->get(route('jobs.index', ['industrial_park_id' => $park->id]))->assertOk();

        $response->assertDontSee($job->title);
    }

    public function test_filter_excludes_job_when_company_location_is_inactive(): void
    {
        $location = CompanyLocation::factory()->create(['administrative_unit_id' => $this->unit->id, 'status' => 'inactive']);
        $job = $this->createListedJob(['title' => 'Job dia diem ngung hoat dong'], companyLocation: $location);

        $response = $this->get(route('jobs.index', ['administrative_unit_id' => $this->unit->id]))->assertOk();

        $response->assertDontSee($job->title);
    }

    public function test_filters_by_administrative_unit(): void
    {
        $unit = AdministrativeUnit::factory()->create();
        $location = CompanyLocation::factory()->create(['administrative_unit_id' => $unit->id, 'status' => 'active']);
        $match = $this->createListedJob(['title' => 'Job dung tinh'], companyLocation: $location);

        $other = $this->createListedJob(['title' => 'Job tinh khac']);

        $response = $this->get(route('jobs.index', ['administrative_unit_id' => $unit->id]))->assertOk();

        $response->assertSee($match->title);
        $response->assertDontSee($other->title);
    }

    public function test_filters_by_company(): void
    {
        $company = Company::factory()->create();
        $match = $this->createListedJob(['company_id' => $company->id, 'title' => 'Job dung cong ty']);
        $other = $this->createListedJob(['title' => 'Job cong ty khac']);

        $response = $this->get(route('jobs.index', ['company_id' => $company->id]))->assertOk();

        $response->assertSee($match->title);
        $response->assertDontSee($other->title);
    }

    public function test_filters_by_work_shift(): void
    {
        $shift = WorkShift::factory()->create();
        $match = $this->createListedJob(['title' => 'Job ca dung'], workShift: $shift);
        $other = $this->createListedJob(['title' => 'Job ca khac']);

        $response = $this->get(route('jobs.index', ['work_shift_id' => $shift->id]))->assertOk();

        $response->assertSee($match->title);
        $response->assertDontSee($other->title);
    }

    public function test_filters_by_salary_bucket(): void
    {
        $inBucket = $this->createListedJob([
            'title' => 'Job luong 10-15',
            'salary_min' => 11_000_000,
            'salary_max' => 14_000_000,
            'salary_period' => 'month',
        ]);
        $outOfBucket = $this->createListedJob([
            'title' => 'Job luong tren 50',
            'salary_min' => 60_000_000,
            'salary_max' => 70_000_000,
            'salary_period' => 'month',
        ]);
        $negotiable = $this->createListedJob([
            'title' => 'Job thoa thuan',
            'salary_min' => null,
            'salary_max' => null,
            'salary_period' => 'negotiable',
        ]);

        $response = $this->get(route('jobs.index', ['salary' => '10-15']))->assertOk();
        $response->assertSee($inBucket->title);
        $response->assertDontSee($outOfBucket->title);
        $response->assertDontSee($negotiable->title);

        $response = $this->get(route('jobs.index', ['salary' => 'thoa-thuan']))->assertOk();
        $response->assertSee($negotiable->title);
        $response->assertDontSee($inBucket->title);
    }

    public function test_filters_by_shuttle_bus_and_accommodation(): void
    {
        $withBoth = $this->createListedJob(['title' => 'Job co xe co cho o', 'has_shuttle_bus' => true, 'has_accommodation' => true]);
        $withNeither = $this->createListedJob(['title' => 'Job khong xe khong cho o', 'has_shuttle_bus' => false, 'has_accommodation' => false]);

        $response = $this->get(route('jobs.index', ['shuttle_bus' => '1']))->assertOk();
        $response->assertSee($withBoth->title);
        $response->assertDontSee($withNeither->title);

        $response = $this->get(route('jobs.index', ['accommodation' => '1']))->assertOk();
        $response->assertSee($withBoth->title);
        $response->assertDontSee($withNeither->title);
    }

    public function test_combined_filters_do_not_duplicate_job_with_multiple_locations_and_shifts(): void
    {
        $park = IndustrialPark::factory()->create(['administrative_unit_id' => $this->unit->id]);
        $primaryLocation = CompanyLocation::factory()->create(['industrial_park_id' => $park->id, 'administrative_unit_id' => null, 'status' => 'active']);
        $job = $this->createListedJob(['title' => 'Job nhieu dia diem ca'], companyLocation: $primaryLocation);

        // Them them 1 dia diem va 1 ca lam viec nua cho cung Job.
        JobLocation::factory()->create([
            'job_id' => $job->id,
            'company_location_id' => CompanyLocation::factory()->create(['industrial_park_id' => $park->id, 'administrative_unit_id' => null, 'status' => 'active'])->id,
            'is_primary' => false,
        ]);
        JobWorkShift::factory()->create([
            'job_id' => $job->id,
            'work_shift_id' => WorkShift::factory()->create()->id,
        ]);

        $response = $this->get(route('jobs.index', ['industrial_park_id' => $park->id]));
        $response->assertOk();

        $count = substr_count($response->getContent(), $job->title);
        $this->assertSame(1, $count, 'Job voi nhieu dia diem/ca khong duoc xuat hien trung lap.');
    }

    public function test_pagination_links_preserve_query_string_filters(): void
    {
        $company = Company::factory()->create();
        Job::factory()->count(15)->create([
            'company_id' => $company->id,
            'status' => 'published',
            'owner_branch_id' => $this->branch->id,
        ])
            ->each(function (Job $job) {
                JobLocation::factory()->create([
                    'job_id' => $job->id,
                    'company_location_id' => CompanyLocation::factory()->create([
                        'company_id' => $job->company_id,
                        'administrative_unit_id' => null,
                    ])->id,
                    'is_primary' => true,
                ]);
            });

        $response = $this->get(route('jobs.index', ['company_id' => $company->id, 'page' => 1]))->assertOk();

        $response->assertSee('company_id='.$company->id, false);
    }

    public function test_invalid_filter_input_does_not_cause_server_error(): void
    {
        $response = $this->get(route('jobs.index', [
            'industrial_park_id' => 'not-a-number',
            'salary' => 'invalid-bucket',
            'sort' => 'invalid-sort',
        ]));

        $this->assertNotSame(500, $response->getStatusCode());
    }

    public function test_non_existent_but_well_formed_filter_id_returns_empty_result_not_error(): void
    {
        $response = $this->get(route('jobs.index', ['industrial_park_id' => 999999]))->assertOk();

        $response->assertSee('Không tìm thấy việc làm phù hợp bộ lọc hiện tại.');
    }

    public function test_listing_query_count_does_not_grow_with_number_of_jobs(): void
    {
        $makeJobs = function (int $count) {
            for ($i = 0; $i < $count; $i++) {
                $this->createListedJob(['title' => 'Job N+1 '.uniqid()]);
            }
        };

        $makeJobs(3);
        DB::enableQueryLog();
        $this->get(route('jobs.index'))->assertOk();
        $queriesForThree = count(DB::getQueryLog());
        DB::flushQueryLog();
        DB::disableQueryLog();

        $makeJobs(9);

        DB::enableQueryLog();
        $this->get(route('jobs.index'))->assertOk();
        $queriesForTwelve = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(
            $queriesForThree,
            $queriesForTwelve,
            'So luong query khong duoc tang theo so Job (N+1).'
        );
    }
}
