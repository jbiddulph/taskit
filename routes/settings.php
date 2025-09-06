<?php

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

    Route::get('settings/company-logo', function () {
        $user = Auth::user();
        $user->load('company'); // Ensure company is loaded
        
        return Inertia::render('settings/CompanyLogo', [
            'user' => $user,
            'company' => $user->company,
        ]);
    })->name('company-logo');

    // Company logo routes (only for paid plans)
    Route::post('settings/company-logo/upload', [App\Http\Controllers\CompanyLogoController::class, 'upload'])->name('company-logo.upload');
    Route::post('settings/company-logo/update-url', [App\Http\Controllers\CompanyLogoController::class, 'updateUrl'])->name('company-logo.update-url');
    Route::delete('settings/company-logo/remove', [App\Http\Controllers\CompanyLogoController::class, 'remove'])->name('company-logo.remove');
});
