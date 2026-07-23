<?php

namespace Tests\Unit\Support;

use App\Models\Job;
use App\Support\JobVerificationWarning;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class JobVerificationWarningTest extends TestCase
{
    /** @var array{warning: int, critical: int} */
    private const array THRESHOLDS = ['warning' => 7, 'critical' => 14];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 1, 15, 12, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function publishedJob(int $daysSinceVerified): Job
    {
        return new Job([
            'status' => 'published',
            'last_verified_at' => now()->subDays($daysSinceVerified),
            'published_at' => now()->subDays($daysSinceVerified + 100),
        ]);
    }

    public function test_warning_boundary_6_7_8_days_since_last_verified(): void
    {
        $this->assertNull(JobVerificationWarning::level($this->publishedJob(6), self::THRESHOLDS));
        $this->assertNull(JobVerificationWarning::level($this->publishedJob(7), self::THRESHOLDS));
        $this->assertSame('warning', JobVerificationWarning::level($this->publishedJob(8), self::THRESHOLDS));
    }

    public function test_critical_boundary_13_14_15_days_since_last_verified(): void
    {
        $this->assertSame('warning', JobVerificationWarning::level($this->publishedJob(13), self::THRESHOLDS));
        $this->assertSame('warning', JobVerificationWarning::level($this->publishedJob(14), self::THRESHOLDS));
        $this->assertSame('critical', JobVerificationWarning::level($this->publishedJob(15), self::THRESHOLDS));
    }

    public function test_falls_back_to_published_at_when_never_verified(): void
    {
        $neverVerifiedFresh = new Job([
            'status' => 'published',
            'last_verified_at' => null,
            'published_at' => now()->subDays(6),
        ]);
        $neverVerifiedStale = new Job([
            'status' => 'published',
            'last_verified_at' => null,
            'published_at' => now()->subDays(8),
        ]);

        $this->assertNull(JobVerificationWarning::level($neverVerifiedFresh, self::THRESHOLDS));
        $this->assertSame('warning', JobVerificationWarning::level($neverVerifiedStale, self::THRESHOLDS));
    }

    public function test_draft_never_has_a_warning_level_regardless_of_age(): void
    {
        $draft = new Job([
            'status' => 'draft',
            'last_verified_at' => null,
            'published_at' => null,
        ]);

        $this->assertNull(JobVerificationWarning::level($draft, self::THRESHOLDS));
    }

    public function test_paused_and_closed_jobs_never_have_a_warning_level(): void
    {
        $paused = new Job([
            'status' => 'paused',
            'last_verified_at' => now()->subDays(30),
            'published_at' => now()->subDays(100),
        ]);
        $closed = new Job([
            'status' => 'closed',
            'last_verified_at' => now()->subDays(30),
            'published_at' => now()->subDays(100),
        ]);

        $this->assertNull(JobVerificationWarning::level($paused, self::THRESHOLDS));
        $this->assertNull(JobVerificationWarning::level($closed, self::THRESHOLDS));
    }

    public function test_job_without_any_reference_time_has_no_level(): void
    {
        $job = new Job(['status' => 'published', 'last_verified_at' => null, 'published_at' => null]);

        $this->assertNull(JobVerificationWarning::level($job, self::THRESHOLDS));
    }
}
