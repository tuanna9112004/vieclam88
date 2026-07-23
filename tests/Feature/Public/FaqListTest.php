<?php

namespace Tests\Feature\Public;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqListTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_faqs_are_listed_in_sort_order(): void
    {
        $second = Faq::factory()->create(['question' => 'Câu hỏi B', 'sort_order' => 2]);
        $first = Faq::factory()->create(['question' => 'Câu hỏi A', 'sort_order' => 1]);
        $inactive = Faq::factory()->inactive()->create(['question' => 'Câu hỏi ẩn']);

        $response = $this->get(route('faqs.index'))->assertOk();

        $response->assertSeeInOrder([$first->question, $second->question]);
        $response->assertDontSee($inactive->question);
        $response->assertSee('<link rel="canonical" href="'.route('faqs.index').'">', false);
    }

    public function test_empty_faq_list_shows_empty_state(): void
    {
        $this->get(route('faqs.index'))
            ->assertOk()
            ->assertSee('Chưa có câu hỏi thường gặp nào.');
    }

    public function test_sitemap_always_includes_faq_index(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('faqs.index'), false);
    }
}
