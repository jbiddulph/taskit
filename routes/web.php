<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\RedemptionController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SubdomainController;
use App\Http\Middleware\SubdomainMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Todo;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/demo', function () {
    return Inertia::render('Demo');
})->name('demo');

Route::get('/privacy', function () {
    return Inertia::render('Privacy');
})->name('privacy');

Route::get('/cookies', function () {
    return Inertia::render('Cookies');
})->name('cookies');

Route::get('/terms', function () {
    return Inertia::render('Terms');
})->name('terms');

// Marketing pages
Route::get('/competitors', function () {
    return Inertia::render('Competitors');
})->name('competitors');

Route::get('/alternative-to', function () {
    return Inertia::render('AlternativeTo');
})->name('alternative-to');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// LTD redemption: available as soon as the user has an account (no email verification required)
Route::middleware(['auth'])->group(function () {
    Route::get('/ltd/redeem', [RedemptionController::class, 'show'])->name('ltd.redeem.show');
    Route::post('/ltd/redeem', [RedemptionController::class, 'redeem'])->name('ltd.redeem');
});

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

// Todo detail route (apex domain): redirect to the company's subdomain URL for the todo
Route::get('/todos/{todo}', function (Request $request, Todo $todo) {
    $highlight = $request->query('highlight');

    $user = $todo->user; // author/owner of the todo
    $company = $user?->company;

    // If company has a subdomain, redirect to it to ensure correct SPA routing/auth cookie
    if ($company && $company->subdomain) {
        $host = $request->getHost();
        // Build subdomain host (handles www/apex)
        $baseHost = preg_replace('/^(www\.)?/i', '', $host);
        $targetHost = $company->subdomain . '.' . $baseHost;
        $scheme = $request->isSecure() ? 'https' : 'http';
        $path = "/todos/{$todo->id}" . ($highlight ? "?highlight=" . urlencode($highlight) : '');
        return redirect()->away("{$scheme}://{$targetHost}{$path}");
    }

    // Fallback: render SPA page on apex (requires user to be logged in on apex)
    return Inertia::render('Todos/Show', [
        'todoId' => (int) $todo->id,
        'highlight' => $highlight ? (int) $highlight : null,
    ]);
})->name('todos.show.web');
Route::get('/debug-env', function () {
    return response()->json([
        'heroku_app_name' => env('HEROKU_APP_NAME'),
        'heroku_app_name_config' => config('services.heroku.app_name'),
        'heroku_api_length' => strlen(env('HEROKU_API')),
        'cloudflare_zone_id' => env('CLOUDFLARE_ZONE_ID'),
        'cloudflare_api_length' => strlen(env('CLOUDFLARE_API'))
    ]);
});
