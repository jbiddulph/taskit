<?php

use App\Http\Controllers\Settings\CompanyController;
use App\Http\Controllers\Settings\DashboardController;
use App\Http\Controllers\Settings\ExportImportController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'subscription.access'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');

    Route::get('settings/dashboard', [DashboardController::class, 'index'])->name('dashboard.settings');
    Route::patch('settings/dashboard', [DashboardController::class, 'update'])->name('dashboard.settings.update');

    // Export/Import routes
    Route::get('settings/export-import', [ExportImportController::class, 'index'])->name('export-import.settings');
    Route::post('settings/export', [ExportImportController::class, 'export'])->name('export.settings');
    Route::post('settings/import', [ExportImportController::class, 'import'])->name('import.settings');

    // Company settings (replaces company-logo)
    Route::get('settings/company', function () {
        $user = Auth::user();
        $user->load('company'); // Ensure company is loaded
        
        return Inertia::render('settings/Company', [
            'user' => $user,
            'company' => $user->company,
        ]);
    })->name('company');

// Company subdomain routes
Route::post('settings/company/subdomain', [CompanyController::class, 'createSubdomain'])->name('company.subdomain.create');
Route::delete('settings/company/subdomain', [CompanyController::class, 'deleteSubdomain'])->name('company.subdomain.delete');
Route::get('settings/company/api-permissions', [CompanyController::class, 'checkApiPermissions'])->name('company.api-permissions');
Route::get('settings/company/test-api-key', [CompanyController::class, 'testApiKey'])->name('company.test-api-key');

// Company public dashboard routes
Route::patch('settings/company/public', [CompanyController::class, 'togglePublic'])->name('company.public.toggle');

// Company name update route
Route::patch('settings/company/name', [CompanyController::class, 'updateName'])->name('company.name.update');

// Company subdomain validation routes
Route::middleware(['auth'])->group(function () {
    Route::get('settings/company/check-subdomain', [CompanyController::class, 'checkSubdomainAvailability'])->name('company.subdomain.check');
});

    // Company logo routes (only for paid plans)
    Route::post('settings/company-logo/upload', [App\Http\Controllers\CompanyLogoController::class, 'upload'])->name('company-logo.upload');
    Route::post('settings/company-logo/update-url', [App\Http\Controllers\CompanyLogoController::class, 'updateUrl'])->name('company-logo.update-url');
    Route::delete('settings/company-logo/remove', [App\Http\Controllers\CompanyLogoController::class, 'remove'])->name('company-logo.remove');
});
