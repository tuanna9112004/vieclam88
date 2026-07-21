<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $jobs = Job::query()
            ->publiclyListed()
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at']);

        $xml = view('public.sitemap', ['jobs' => $jobs])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
