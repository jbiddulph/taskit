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

// Subdomain routes are handled in routes/subdomain.php
// and loaded conditionally by SubdomainMiddleware

// Debug route to test subdomain detection
Route::get('/debug-subdomain', function (Request $request) {
    return response()->json([
        'host' => $request->getHost(),
        'is_subdomain' => $request->attributes->get('isSubdomain', false),
        'company' => $request->attributes->get('company'),
    ]);
});

// Debug route to test www redirect
Route::get('/debug-www', function (Request $request) {
    return response()->json([
        'host' => $request->getHost(),
        'url' => $request->url(),
        'full_url' => $request->fullUrl(),
        'secure' => $request->secure(),
        'headers' => $request->headers->all(),
    ]);
});

// Debug route to test subdomain and public route
Route::get('/debug-public', function (Request $request) {
    $company = \App\Models\Company::where('subdomain', 'moe')->first();
    return response()->json([
        'host' => $request->getHost(),
        'path' => $request->path(),
        'is_subdomain' => $request->attributes->get('isSubdomain', false),
        'company' => $company ? [
            'id' => $company->id,
            'name' => $company->name,
            'subdomain' => $company->subdomain,
            'is_public' => $company->is_public
        ] : null,
        'middleware_processed' => $request->attributes->has('company')
    ]);
});

// Debug route to check environment variables
Route::get('/debug-env', function () {
    return response()->json([
        'heroku_app_name' => env('HEROKU_APP_NAME'),
        'heroku_app_name_config' => config('services.heroku.app_name'),
        'heroku_api_length' => strlen(env('HEROKU_API')),
        'cloudflare_zone_id' => env('CLOUDFLARE_ZONE_ID'),
        'cloudflare_api_length' => strlen(env('CLOUDFLARE_API'))
    ]);
});
