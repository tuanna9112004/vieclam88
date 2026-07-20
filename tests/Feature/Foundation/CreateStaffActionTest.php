<?php

namespace Tests\Feature\Foundation;

use App\Actions\User\CreateStaffAction;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateStaffActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_staff_with_branch(): void
    {
        $branch = Branch::factory()->create();

        $staff = (new CreateStaffAction)->handle([
            'name' => 'Nguyễn Văn A',
            'email' => 'a@vieclam88.test',
            'branch_id' => $branch->id,
            'password' => 'temp-password-123',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'role' => 'staff',
            'branch_id' => $branch->id,
        ]);
        $this->assertNull($staff->password_changed_at);
    }

    public function test_rejects_staff_without_branch(): void
    {
        $this->expectException(ValidationException::class);

        (new CreateStaffAction)->handle([
            'name' => 'Nguyễn Văn A',
            'email' => 'a@vieclam88.test',
            'branch_id' => null,
            'password' => 'temp-password-123',
        ]);
    }
}
