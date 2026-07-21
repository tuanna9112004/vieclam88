<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $phone = '09'.fake()->unique()->numerify('########');
        $fullName = fake()->name();

        return [
            'public_id' => (string) Str::ulid(),
            'candidate_id' => Candidate::factory(),
            'job_id' => Job::factory(),
            'source_id' => null,
            'owner_branch_id' => Branch::factory(),
            'stage' => 'new',
            'stage_changed_at' => now(),
            'close_reason' => null,
            'workflow_cycle' => 1,
            'workflow_cycle_started_at' => now(),
            'reopened_at' => null,
            'reopened_by' => null,
            'submission_token' => bin2hex(random_bytes(32)),
            'needs_duplicate_review' => false,
            'duplicate_reviewed_at' => null,
            'duplicate_reviewed_by' => null,
            'last_reapplied_at' => null,
            'submitted_full_name' => $fullName,
            'submitted_phone' => $phone,
            'submitted_phone_normalized' => $phone,
            'submission_snapshot' => ['full_name' => $fullName, 'phone' => $phone],
            'job_snapshot' => ['title' => fake()->jobTitle()],
            'source_detail' => null,
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'landing_url' => null,
            'consent_version' => 'v1',
            'consent_text_hash' => hash('sha256', 'consent-v1'),
            'consented_at' => now(),
            'consent_ip' => '127.0.0.1',
            'consent_user_agent' => null,
            'expected_start_at' => null,
            'started_at' => null,
            'closed_at' => null,
        ];
    }
}
