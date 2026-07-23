<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Faq\StoreFaqRequest;
use App\Http\Requests\Hr\Faq\UpdateFaqRequest;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Faq::class);

        $faqs = Faq::query()->orderBy('sort_order')->orderBy('id')->paginate(20);

        return view('hr.faqs.index', compact('faqs'));
    }

    public function create(): View
    {
        $this->authorize('create', Faq::class);

        return view('hr.faqs.create');
    }

    public function store(StoreFaqRequest $request): RedirectResponse
    {
        Faq::create($request->validated());

        return redirect()->route('hr.faqs.index')->with('status', 'Đã tạo câu hỏi.');
    }

    public function edit(Faq $faq): View
    {
        $this->authorize('update', $faq);

        return view('hr.faqs.edit', compact('faq'));
    }

    public function update(UpdateFaqRequest $request, Faq $faq): RedirectResponse
    {
        $faq->update($request->validated());

        return redirect()->route('hr.faqs.index')->with('status', 'Đã cập nhật câu hỏi.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $this->authorize('delete', $faq);

        $faq->delete();

        return redirect()->route('hr.faqs.index')->with('status', 'Đã xóa câu hỏi.');
    }
}
