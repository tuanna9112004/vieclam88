<?php

use App\Http\Controllers\Hr\Auth\HrAuthController;
use App\Http\Controllers\Hr\BranchController;
use App\Http\Controllers\Hr\CompanyContactController;
use App\Http\Controllers\Hr\CompanyController;
use App\Http\Controllers\Hr\CompanyLocationController;
use App\Http\Controllers\Hr\DashboardController;
use App\Http\Controllers\Hr\IndustrialParkController;
use App\Http\Controllers\Hr\JobController;
use App\Http\Controllers\Hr\JobVerificationController;
use App\Http\Controllers\Hr\JobWorkflowController;
use App\Http\Controllers\Hr\PasswordChangeController;
use App\Http\Controllers\Hr\StaffController;
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

            Route::prefix('nhan-vien')->name('staff.')->group(function () {
                Route::get('/', [StaffController::class, 'index'])->name('index');
                Route::get('tao-moi', [StaffController::class, 'create'])->name('create');
                Route::post('/', [StaffController::class, 'store'])->name('store');
                Route::get('{staff}/sua', [StaffController::class, 'edit'])->name('edit');
                Route::put('{staff}', [StaffController::class, 'update'])->name('update');
                Route::post('{staff}/khoa', [StaffController::class, 'lock'])->name('lock');
                Route::post('{staff}/mo-khoa', [StaffController::class, 'unlock'])->name('unlock');
                Route::post('{staff}/dat-lai-mat-khau', [StaffController::class, 'resetPassword'])
                    ->name('reset-password');
            });

            Route::prefix('khu-cong-nghiep')->name('industrial-parks.')->group(function () {
                Route::get('/', [IndustrialParkController::class, 'index'])->name('index');
                Route::post('/', [IndustrialParkController::class, 'store'])->name('store');
                Route::put('{industrialPark}', [IndustrialParkController::class, 'update'])->name('update');
            });

            Route::prefix('co-so')->name('branches.')->group(function () {
                Route::get('/', [BranchController::class, 'index'])->name('index');
                Route::get('tao-moi', [BranchController::class, 'create'])->name('create');
                Route::post('/', [BranchController::class, 'store'])->name('store');
                Route::get('{branch}/sua', [BranchController::class, 'edit'])->name('edit');
                Route::put('{branch}', [BranchController::class, 'update'])->name('update');
                Route::delete('{branch}', [BranchController::class, 'destroy'])->name('destroy');
                Route::post('{branch}/khoi-phuc', [BranchController::class, 'restore'])
                    ->name('restore')->withTrashed();
            });

            Route::prefix('cong-ty')->name('companies.')->group(function () {
                Route::get('/', [CompanyController::class, 'index'])->name('index');
                Route::get('tao-moi', [CompanyController::class, 'create'])->name('create');
                Route::post('/', [CompanyController::class, 'store'])->name('store');
                Route::get('{company}/sua', [CompanyController::class, 'edit'])->name('edit');
                Route::put('{company}', [CompanyController::class, 'update'])->name('update');
                Route::delete('{company}', [CompanyController::class, 'destroy'])->name('destroy');
                Route::post('{company}/khoi-phuc', [CompanyController::class, 'restore'])
                    ->name('restore')->withTrashed();
            });

            Route::prefix('cong-ty/{company}/dia-diem')->name('company-locations.')->group(function () {
                Route::get('/', [CompanyLocationController::class, 'index'])->name('index');
                Route::post('/', [CompanyLocationController::class, 'store'])->name('store');
                Route::put('{location}', [CompanyLocationController::class, 'update'])->name('update');
                Route::delete('{location}', [CompanyLocationController::class, 'destroy'])->name('destroy');
                Route::post('{location}/khoi-phuc', [CompanyLocationController::class, 'restore'])
                    ->name('restore')->withTrashed();
            });

            Route::prefix('cong-ty/{company}/dau-moi')->name('company-contacts.')->group(function () {
                Route::get('/', [CompanyContactController::class, 'index'])->name('index');
                Route::post('/', [CompanyContactController::class, 'store'])->name('store');
                Route::put('{contact}', [CompanyContactController::class, 'update'])->name('update');
                Route::delete('{contact}', [CompanyContactController::class, 'destroy'])->name('destroy');
                Route::post('{contact}/khoi-phuc', [CompanyContactController::class, 'restore'])
                    ->name('restore')->withTrashed();
            });

            Route::prefix('viec-lam')->name('jobs.')->group(function () {
                Route::get('/', [JobController::class, 'index'])->name('index');
                Route::get('tao-moi', [JobController::class, 'create'])->name('create');
                Route::post('/', [JobController::class, 'store'])->name('store');
                Route::get('{job}/sua', [JobController::class, 'edit'])->name('edit');
                Route::put('{job}', [JobController::class, 'update'])->name('update');
                Route::post('{job}/xac-nhan', [JobVerificationController::class, 'store'])->name('verify');
                Route::post('{job}/xuat-ban', [JobWorkflowController::class, 'publish'])->name('publish');
            });
        });
    });
});
