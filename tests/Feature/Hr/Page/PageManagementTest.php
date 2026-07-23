<?php

namespace Tests\Feature\Hr\Page;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_map_page_and_faq_routes_are_registered(): void
    {
        foreach ([
            'pages.about',
            'faqs.index',
            'hr.pages.index',
            'hr.pages.create',
            'hr.pages.store',
            'hr.pages.edit',
            'hr.pages.update',
            'hr.pages.destroy',
            'hr.faqs.index',
            'hr.faqs.create',
            'hr.faqs.store',
            'hr.faqs.edit',
            'hr.faqs.update',
            'hr.faqs.destroy',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName), "Thiếu route $routeName.");
        }
    }

    public function test_admin_can_create_edit_and_delete_a_page(): void
    {
        $admin = User::factory()->admin()->create();

        $storeResponse = $this->actingAs($admin)->post(route('hr.pages.store'), [
            'title' => 'Giới thiệu',
            'slug' => 'gioi-thieu',
            'content' => 'Nội dung giới thiệu.',
            'meta_title' => null,
            'meta_description' => null,
            'status' => 'published',
        ]);

        $storeResponse->assertRedirect(route('hr.pages.index'));
        $storeResponse->assertSessionHas('status');
        $page = Page::query()->where('slug', 'gioi-thieu')->firstOrFail();
        $this->assertSame('published', $page->status->value);
        $this->assertNotNull($page->published_at);
        $this->assertSame($admin->id, $page->created_by);

        $indexResponse = $this->actingAs($admin)->get(route('hr.pages.index'));
        $indexResponse->assertOk()->assertSee('Giới thiệu');

        $updateResponse = $this->actingAs($admin)->put(route('hr.pages.update', $page), [
            'title' => 'Giới thiệu công ty',
            'slug' => 'gioi-thieu',
            'content' => 'Nội dung mới.',
            'meta_title' => null,
            'meta_description' => null,
            'status' => 'draft',
        ]);

        $updateResponse->assertRedirect(route('hr.pages.index'));
        $page->refresh();
        $this->assertSame('Giới thiệu công ty', $page->title);
        $this->assertSame('draft', $page->status->value);
        $this->assertNull($page->published_at);
        $this->assertSame($admin->id, $page->updated_by);

        $this->actingAs($admin)->delete(route('hr.pages.destroy', $page))
            ->assertRedirect(route('hr.pages.index'));
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_slug_must_be_unique_and_validation_fails_without_writing(): void
    {
        $admin = User::factory()->admin()->create();
        Page::factory()->create(['slug' => 'gioi-thieu']);

        $response = $this->actingAs($admin)->post(route('hr.pages.store'), [
            'title' => '',
            'slug' => 'gioi-thieu',
            'content' => '',
            'status' => 'invalid-status',
        ]);

        $response->assertSessionHasErrors(['title', 'slug', 'content', 'status']);
        $this->assertDatabaseCount('pages', 1);
    }

    public function test_guest_is_redirected_and_staff_receives_403(): void
    {
        $staff = User::factory()->create();
        $page = Page::factory()->create();

        $this->get(route('hr.pages.index'))->assertRedirect(route('hr.login'));

        $this->actingAs($staff)->get(route('hr.pages.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('hr.pages.store'), [
            'title' => 'X',
            'slug' => 'x',
            'content' => 'x',
            'status' => 'draft',
        ])->assertForbidden();
        $this->actingAs($staff)->delete(route('hr.pages.destroy', $page))->assertForbidden();

        $this->assertDatabaseHas('pages', ['id' => $page->id]);
    }
}
