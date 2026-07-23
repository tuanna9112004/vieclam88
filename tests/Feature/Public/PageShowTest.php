<?php

namespace Tests\Feature\Public;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_about_page_is_visible_with_seo_tags(): void
    {
        $page = Page::factory()->published()->create([
            'slug' => 'gioi-thieu',
            'title' => 'Giới thiệu vieclam88',
            'content' => 'Nội dung công khai.',
        ]);

        $response = $this->get(route('pages.about'))->assertOk();

        $response->assertSee($page->title);
        $response->assertSee('Nội dung công khai.');
        $response->assertSee('<link rel="canonical" href="'.route('pages.about').'">', false);
    }

    public function test_draft_or_hidden_about_page_returns_404(): void
    {
        Page::factory()->create(['slug' => 'gioi-thieu', 'status' => 'draft']);

        $this->get(route('pages.about'))->assertNotFound();
    }

    public function test_missing_about_page_returns_404(): void
    {
        $this->get(route('pages.about'))->assertNotFound();
    }

    public function test_sitemap_only_includes_about_page_when_published(): void
    {
        $this->get(route('sitemap'))->assertOk()->assertDontSee(route('pages.about'), false);

        Page::factory()->published()->create(['slug' => 'gioi-thieu']);

        $this->get(route('sitemap'))->assertOk()->assertSee(route('pages.about'), false);
    }
}
