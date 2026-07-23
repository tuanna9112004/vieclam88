<?php

namespace App\Actions\Job;

use App\Models\Job;
use App\Models\JobBranchHistory;
use App\Models\JobLocation;
use App\Models\JobWorkShift;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DuplicateJobAction
{
    private const BUSINESS_FIELDS = [
        'employment_type',
        'quantity',
        'gender_requirement',
        'min_age',
        'max_age',
        'education_requirement',
        'experience_requirement',
        'salary_min',
        'salary_max',
        'salary_base',
        'salary_period',
        'currency',
        'salary_description',
        'job_description',
        'requirements',
        'benefits',
        'application_documents',
        'has_shuttle_bus',
        'shuttle_bus_details',
        'has_accommodation',
        'accommodation_details',
        'has_meal_support',
        'meal_support_details',
        'is_urgent',
    ];

    public function __construct(
        protected SaveJobDraftAction $saveJobDraft,
        protected GuardJobReferencesAction $guardReferences
    ) {}

    public function handle(Job $job, User $actor): Job
    {
        return DB::transaction(function () use ($job, $actor) {
            $source = Job::query()->whereKey($job->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('duplicate', $source);

            $this->guardReferences->handle($source);

            $duplicate = $this->saveJobDraft->handle([
                'title' => $source->title,
                'company_id' => $source->company_id,
                'company_contact_id' => $source->company_contact_id,
                'company_location_id' => null,
                'owner_branch_id' => $source->owner_branch_id,
            ], $actor);

            $duplicate->fill(Arr::only($source->getAttributes(), self::BUSINESS_FIELDS));
            $duplicate->save();

            foreach ($source->jobLocations()->get(['company_location_id', 'is_primary']) as $location) {
                JobLocation::create([
                    'job_id' => $duplicate->id,
                    'company_location_id' => $location->company_location_id,
                    'is_primary' => $location->is_primary,
                ]);
            }

            foreach ($source->jobWorkShifts()->get(['work_shift_id', 'description']) as $shift) {
                JobWorkShift::create([
                    'job_id' => $duplicate->id,
                    'work_shift_id' => $shift->work_shift_id,
                    'description' => $shift->description,
                ]);
            }

            JobBranchHistory::create([
                'job_id' => $duplicate->id,
                'from_branch_id' => null,
                'to_branch_id' => $duplicate->owner_branch_id,
                'reason' => "Nhân bản từ Job {$source->code}.",
                'changed_by' => $actor->id,
            ]);

            return $duplicate->fresh();
        });
    }
}
