<?php

use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\JobController;
use App\Http\Controllers\Public\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/viec-lam', [JobController::class, 'index'])->name('jobs.index');
Route::get('/viec-lam/{job:slug}', [JobController::class, 'show'])->name('jobs.show');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('sitemap');
