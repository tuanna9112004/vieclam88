<?php

use App\Http\Controllers\Hr\Auth\HrAuthController;
use App\Http\Controllers\Hr\DashboardController;
use App\Http\Controllers\Hr\PasswordChangeController;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::prefix('hr')->name('hr.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('dang-nhap', [HrAuthController::class, 'create'])->name('login');
        Route::post('dang-nhap', [HrAuthController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'role:staff,admin', EnsureUserIsActive::class])->group(function () {
        Route::post('dang-xuat', [HrAuthController::class, 'destroy'])->name('logout');

        Route::get('doi-mat-khau', [PasswordChangeController::class, 'edit'])->name('password.change');
        Route::put('doi-mat-khau', [PasswordChangeController::class, 'update'])->name('password.update');

        Route::middleware(EnsurePasswordChanged::class)->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        });
    });
});
