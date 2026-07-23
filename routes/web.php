<?php

use App\Http\Controllers\Public\ApplicationController;
use App\Http\Controllers\Public\CompanyController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\FaqController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\IndustrialParkController;
use App\Http\Controllers\Public\JobController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/cong-ty', [CompanyController::class, 'index'])->name('companies.index');
Route::get('/cong-ty/{company:slug}', [CompanyController::class, 'show'])->name('companies.show');
Route::get('/khu-cong-nghiep/{industrialPark:slug}', [IndustrialParkController::class, 'show'])
    ->name('industrial-parks.show');
Route::get('/gioi-thieu', [PageController::class, 'about'])->name('pages.about');
Route::get('/lien-he', [ContactController::class, 'show'])->name('contact.show');
Route::get('/cau-hoi-thuong-gap', [FaqController::class, 'index'])->name('faqs.index');

Route::get('/viec-lam', [JobController::class, 'index'])->name('jobs.index');
Route::get('/viec-lam/{job:slug}', [JobController::class, 'show'])->name('jobs.show');
Route::post('/viec-lam/{job:slug}/ung-tuyen', [ApplicationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('applications.store');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('sitemap');
