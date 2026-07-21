<?php

namespace Tests\Feature\Foundation;

use App\Models\Job;
use App\Models\JobWorkShift;
use App\Models\WorkShift;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobWorkShiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_and_work_shift_pair_must_be_unique(): void
    {
        $job = Job::factory()->create();
        $shift = WorkShift::factory()->create();
        JobWorkShift::factory()->create(['job_id' => $job->id, 'work_shift_id' => $shift->id]);

        $this->expectException(QueryException::class);

        JobWorkShift::factory()->create(['job_id' => $job->id, 'work_shift_id' => $shift->id]);
    }

    public function test_job_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        JobWorkShift::factory()->create(['job_id' => null]);
    }

    public function test_work_shift_id_is_required(): void
    {
        $this->expectException(QueryException::class);

        JobWorkShift::factory()->create(['work_shift_id' => null]);
    }

    public function test_deleting_job_cascades_job_work_shifts(): void
    {
        $job = Job::factory()->create();
        $shift = WorkShift::factory()->create();
        JobWorkShift::factory()->create(['job_id' => $job->id, 'work_shift_id' => $shift->id]);

        $job->forceDelete();

        $this->assertDatabaseMissing('job_work_shifts', ['job_id' => $job->id]);
    }

    public function test_deleting_work_shift_referenced_by_job_is_restricted(): void
    {
        $job = Job::factory()->create();
        $shift = WorkShift::factory()->create();
        JobWorkShift::factory()->create(['job_id' => $job->id, 'work_shift_id' => $shift->id]);

        $this->expectException(QueryException::class);

        $shift->delete();
    }

    public function test_a_job_can_have_multiple_work_shifts(): void
    {
        $job = Job::factory()->create();
        $shiftA = WorkShift::factory()->create();
        $shiftB = WorkShift::factory()->create();
        JobWorkShift::factory()->create(['job_id' => $job->id, 'work_shift_id' => $shiftA->id]);
        JobWorkShift::factory()->create(['job_id' => $job->id, 'work_shift_id' => $shiftB->id]);

        $this->assertSame(2, JobWorkShift::where('job_id', $job->id)->count());
    }

    public function test_description_is_nullable(): void
    {
        $jobWorkShift = JobWorkShift::factory()->create(['description' => null]);

        $this->assertDatabaseHas('job_work_shifts', [
            'job_id' => $jobWorkShift->job_id,
            'work_shift_id' => $jobWorkShift->work_shift_id,
            'description' => null,
        ]);
    }

    public function test_belongs_to_job_and_work_shift(): void
    {
        $job = Job::factory()->create();
        $shift = WorkShift::factory()->create();
        $jobWorkShift = JobWorkShift::factory()->create(['job_id' => $job->id, 'work_shift_id' => $shift->id]);

        $this->assertTrue($jobWorkShift->job->is($job));
        $this->assertTrue($jobWorkShift->workShift->is($shift));
        $this->assertTrue($job->jobWorkShifts()->first()->workShift->is($shift));
    }
}
