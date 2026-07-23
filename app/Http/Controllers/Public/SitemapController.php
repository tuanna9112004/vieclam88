<?php

namespace App\Http\Controllers\Public;

use App\Enums\PageStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\IndustrialPark;
use App\Models\Job;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $jobs = Job::query()
            ->publiclyListed()
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at']);

        $companies = Company::query()
            ->where('status', 'active')
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at']);

        $industrialParks = IndustrialPark::query()
            ->where('is_active', true)
            ->whereHas('administrativeUnit', fn (Builder $query) => $query->where('is_active', true))
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at']);

        $aboutPagePublished = Page::query()
            ->where('slug', 'gioi-thieu')
            ->where('status', PageStatus::Published->value)
            ->exists();

        $xml = view('public.sitemap', [
            'jobs' => $jobs,
            'companies' => $companies,
            'industrialParks' => $industrialParks,
            'aboutPagePublished' => $aboutPagePublished,
        ])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
