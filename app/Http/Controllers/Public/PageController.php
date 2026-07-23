<?php

namespace App\Http\Controllers\Public;

use App\Enums\PageStatus;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    private const ABOUT_SLUG = 'gioi-thieu';

    public function about(): View
    {
        $page = Page::query()
            ->where('slug', self::ABOUT_SLUG)
            ->where('status', PageStatus::Published->value)
            ->firstOrFail();

        return view('public.pages.about', ['page' => $page]);
    }
}
