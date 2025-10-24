<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\SubdomainController;
use App\Http\Middleware\SubdomainMiddleware;
use Illuminate\Http\Request;
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

// Client and Company management routes
Route::middleware(['auth', 'verified', 'subscription.access'])->group(function () {
    Route::resource('clients', ClientController::class);
    Route::get('companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
    Route::get('companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/subscription.php';

// Subdomain routes - these will be handled by SubdomainMiddleware
// Only apply to actual subdomains, not the main domain
Route::middleware([SubdomainMiddleware::class])->group(function () {
    Route::get('/', [SubdomainController::class, 'company'])->name('subdomain.company');
    Route::get('/login', [SubdomainController::class, 'login'])->name('subdomain.login');
    Route::post('/login', [SubdomainController::class, 'authenticate'])->name('subdomain.authenticate');
    Route::get('/dashboard', [SubdomainController::class, 'dashboard'])->name('subdomain.dashboard');
})->where('host', '^(?!www\.)[a-zA-Z0-9-]+\.zaptask\.co\.uk$');

// Debug route to test subdomain detection
Route::get('/debug-subdomain', function (Request $request) {
    return response()->json([
        'host' => $request->getHost(),
        'is_subdomain' => $request->attributes->get('isSubdomain', false),
        'company' => $request->attributes->get('company'),
    ]);
});
