<?php

namespace App\Http\Controllers\Hr;

use App\Enums\PageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Page\StorePageRequest;
use App\Http\Requests\Hr\Page\UpdatePageRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Page::class);

        $pages = Page::query()->orderBy('title')->paginate(20);

        return view('hr.pages.index', compact('pages'));
    }

    public function create(): View
    {
        $this->authorize('create', Page::class);

        return view('hr.pages.create');
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['published_at'] = $this->resolvePublishedAt($data['status'], null);
        $data['created_by'] = $request->user()->id;

        Page::create($data);

        return redirect()->route('hr.pages.index')->with('status', 'Đã tạo trang.');
    }

    public function edit(Page $page): View
    {
        $this->authorize('update', $page);

        return view('hr.pages.edit', compact('page'));
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $data = $request->validated();
        $data['published_at'] = $this->resolvePublishedAt($data['status'], $page->published_at);
        $data['updated_by'] = $request->user()->id;

        $page->update($data);

        return redirect()->route('hr.pages.index')->with('status', 'Đã cập nhật trang.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $this->authorize('delete', $page);

        $page->delete();

        return redirect()->route('hr.pages.index')->with('status', 'Đã xóa trang.');
    }

    /**
     * `published_at` không nhận input client — giữ mốc lần đầu published, xóa khi không còn published.
     */
    protected function resolvePublishedAt(string $status, mixed $currentPublishedAt): mixed
    {
        if ($status !== PageStatus::Published->value) {
            return null;
        }

        return $currentPublishedAt ?? now();
    }
}
