<?php

namespace Tests\Feature\Hr\Authorization;

use App\Models\Application;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\CandidateDuplicateReview;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchAuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_role_policy_matrix_enforces_branch_boundaries(): void
    {
        fake()->unique(true);

        $branchA = Branch::factory()->create(['status' => 'active']);
        $branchB = Branch::factory()->create(['status' => 'active']);
        $superAdmin = User::factory()->superAdmin()->create();
        $branchAdmin = User::factory()->branchAdmin()->create(['branch_id' => $branchA->id]);
        $staffA = User::factory()->create(['branch_id' => $branchA->id]);
        $staffB = User::factory()->create(['branch_id' => $branchB->id]);
        $jobA = Job::factory()->create(['owner_branch_id' => $branchA->id]);
        $jobB = Job::factory()->create(['owner_branch_id' => $branchB->id]);
        $candidateA = Candidate::factory()->create();
        $candidateB = Candidate::factory()->create();
        $applicationA = Application::factory()->create([
            'candidate_id' => $candidateA->id,
            'job_id' => $jobA->id,
            'owner_branch_id' => $branchA->id,
        ]);
        $applicationB = Application::factory()->create([
            'candidate_id' => $candidateB->id,
            'job_id' => $jobB->id,
            'owner_branch_id' => $branchB->id,
        ]);
        $duplicateReview = CandidateDuplicateReview::factory()->create([
            'application_id' => $applicationA->id,
            'candidate_id' => $candidateA->id,
            'suspected_candidate_id' => $candidateB->id,
        ]);

        $matrix = [
            'super_admin' => [
                'actor' => $superAdmin,
                'branch_a_data' => true,
                'branch_b_data' => true,
                'manage_staff_a' => true,
                'manage_staff_b' => true,
                'manage_branch_a' => true,
                'manage_branch_b' => true,
                'staff_index' => true,
                'branch_index' => true,
                'duplicate_review' => true,
            ],
            'branch_admin' => [
                'actor' => $branchAdmin,
                'branch_a_data' => true,
                'branch_b_data' => false,
                'manage_staff_a' => true,
                'manage_staff_b' => false,
                'manage_branch_a' => true,
                'manage_branch_b' => false,
                'staff_index' => true,
                'branch_index' => true,
                'duplicate_review' => false,
            ],
            'staff' => [
                'actor' => $staffA,
                'branch_a_data' => true,
                'branch_b_data' => false,
                'manage_staff_a' => false,
                'manage_staff_b' => false,
                'manage_branch_a' => false,
                'manage_branch_b' => false,
                'staff_index' => false,
                'branch_index' => false,
                'duplicate_review' => false,
            ],
        ];

        foreach ($matrix as $role => $expected) {
            /** @var User $actor */
            $actor = $expected['actor'];

            $this->assertSame($expected['branch_a_data'], $actor->can('update', $jobA), "{$role}: Job branch A");
            $this->assertSame($expected['branch_b_data'], $actor->can('update', $jobB), "{$role}: Job branch B");
            $this->assertSame($expected['branch_a_data'], $actor->can('view', $applicationA), "{$role}: Application branch A");
            $this->assertSame($expected['branch_b_data'], $actor->can('view', $applicationB), "{$role}: Application branch B");
            $this->assertSame($expected['branch_a_data'], $actor->can('view', $candidateA), "{$role}: Candidate branch A");
            $this->assertSame($expected['branch_b_data'], $actor->can('view', $candidateB), "{$role}: Candidate branch B");
            $this->assertSame($expected['manage_staff_a'], $actor->can('update', $staffA), "{$role}: Staff branch A");
            $this->assertSame($expected['manage_staff_b'], $actor->can('update', $staffB), "{$role}: Staff branch B");
            $this->assertSame($expected['manage_branch_a'], $actor->can('update', $branchA), "{$role}: Branch A");
            $this->assertSame($expected['manage_branch_b'], $actor->can('update', $branchB), "{$role}: Branch B");
            $this->assertSame($expected['staff_index'], $actor->can('viewAny', User::class), "{$role}: Staff index");
            $this->assertSame($expected['branch_index'], $actor->can('viewAny', Branch::class), "{$role}: Branch index");
            $this->assertTrue($actor->can('viewAny', Job::class), "{$role}: Job index");
            $this->assertTrue($actor->can('viewAny', Application::class), "{$role}: Application index");
            $this->assertTrue($actor->can('export', Application::class), "{$role}: Application export");
            $this->assertSame(
                $expected['duplicate_review'],
                $actor->can('view', $duplicateReview),
                "{$role}: Duplicate review"
            );
        }
    }

    public function test_cross_branch_direct_urls_are_forbidden_but_super_admin_is_unscoped(): void
    {
        fake()->unique(true);

        $branchA = Branch::factory()->create(['status' => 'active']);
        $branchB = Branch::factory()->create(['status' => 'active']);
        $superAdmin = User::factory()->superAdmin()->create();
        $branchAdmin = User::factory()->branchAdmin()->create(['branch_id' => $branchA->id]);
        $staffA = User::factory()->create(['branch_id' => $branchA->id]);
        $staffB = User::factory()->create(['branch_id' => $branchB->id]);
        $jobB = Job::factory()->create(['owner_branch_id' => $branchB->id]);
        $candidateB = Candidate::factory()->create();
        $applicationB = Application::factory()->create([
            'candidate_id' => $candidateB->id,
            'job_id' => $jobB->id,
            'owner_branch_id' => $branchB->id,
        ]);

        foreach ([$branchAdmin, $staffA] as $branchUser) {
            $this->actingAs($branchUser)->get(route('hr.jobs.edit', $jobB))->assertForbidden();
            $this->actingAs($branchUser)->get(route('hr.applications.show', $applicationB))->assertForbidden();
            $this->actingAs($branchUser)->get(route('hr.candidates.show', $candidateB))->assertForbidden();
        }

        $this->actingAs($branchAdmin)->get(route('hr.staff.edit', $staffB))->assertForbidden();
        $this->actingAs($staffA)->get(route('hr.staff.index'))->assertForbidden();

        $this->actingAs($superAdmin)->get(route('hr.jobs.edit', $jobB))->assertOk();
        $this->actingAs($superAdmin)->get(route('hr.applications.show', $applicationB))->assertOk();
        $this->actingAs($superAdmin)->get(route('hr.candidates.show', $candidateB))->assertOk();
        $this->actingAs($superAdmin)->get(route('hr.staff.edit', $staffB))->assertOk();
    }
}
