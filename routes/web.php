<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/demo', function () {
    return Inertia::render('Demo');
})->name('demo');

Route::get('/privacy', function () {
    return Inertia::render('Privacy');
})->name('privacy');

Route::get('/terms', function () {
    return Inertia::render('Terms');
})->name('terms');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');





Route::get('dashboard', function () {
    $user = Auth::user();
    $company = $user->company;
    
    return Inertia::render('Dashboard', [
        'user' => $user,
        'company' => $company ? [
            'id' => $company->id,
            'name' => $company->name,
            'code' => $company->code,
            'subscription_type' => $company->subscription_type,
            'current_member_count' => $company->getCurrentMemberCount(),
            'member_limit' => $company->getMemberLimit(),
            'current_project_count' => $company->getCurrentProjectCount(),
            'project_limit' => $company->getProjectLimit(),
        ] : null,
    ]);
})->middleware(['auth', 'verified', 'subscription.access'])->name('dashboard');

// Client management routes
Route::middleware(['auth', 'verified', 'subscription.access'])->group(function () {
    Route::resource('clients', ClientController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/subscription.php';
