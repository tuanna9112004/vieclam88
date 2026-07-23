<?php

namespace Tests\Feature\Hr\Faq;

use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_and_delete_a_faq(): void
    {
        $admin = User::factory()->admin()->create();

        $storeResponse = $this->actingAs($admin)->post(route('hr.faqs.store'), [
            'question' => 'Làm sao để ứng tuyển?',
            'answer' => 'Bấm nút Ứng tuyển trên trang chi tiết việc làm.',
            'is_active' => '1',
            'sort_order' => 1,
        ]);

        $storeResponse->assertRedirect(route('hr.faqs.index'));
        $faq = Faq::query()->firstOrFail();
        $this->assertTrue($faq->is_active);

        $this->actingAs($admin)->get(route('hr.faqs.index'))
            ->assertOk()
            ->assertSee('Làm sao để ứng tuyển?');

        $updateResponse = $this->actingAs($admin)->put(route('hr.faqs.update', $faq), [
            'question' => 'Câu hỏi đã sửa?',
            'answer' => 'Câu trả lời đã sửa.',
            'is_active' => '0',
            'sort_order' => 2,
        ]);

        $updateResponse->assertRedirect(route('hr.faqs.index'));
        $faq->refresh();
        $this->assertSame('Câu hỏi đã sửa?', $faq->question);
        $this->assertFalse($faq->is_active);
        $this->assertSame(2, $faq->sort_order);

        $this->actingAs($admin)->delete(route('hr.faqs.destroy', $faq))
            ->assertRedirect(route('hr.faqs.index'));
        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    public function test_validation_fails_without_writing_any_row(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('hr.faqs.store'), [
            'question' => '',
            'answer' => '',
            'is_active' => 'not-boolean',
            'sort_order' => -1,
        ]);

        $response->assertSessionHasErrors(['question', 'answer', 'is_active', 'sort_order']);
        $this->assertDatabaseCount('faqs', 0);
    }

    public function test_guest_is_redirected_and_staff_receives_403(): void
    {
        $staff = User::factory()->create();
        $faq = Faq::factory()->create();

        $this->get(route('hr.faqs.index'))->assertRedirect(route('hr.login'));

        $this->actingAs($staff)->get(route('hr.faqs.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('hr.faqs.store'), [
            'question' => 'X?',
            'answer' => 'Y',
            'is_active' => '1',
            'sort_order' => 0,
        ])->assertForbidden();
        $this->actingAs($staff)->delete(route('hr.faqs.destroy', $faq))->assertForbidden();

        $this->assertDatabaseHas('faqs', ['id' => $faq->id]);
    }
}
