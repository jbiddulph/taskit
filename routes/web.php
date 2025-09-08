<?php

use App\Http\Controllers\ContactController;
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

// Test route for welcome email preview
Route::get('/test-welcome-email', function () {
    // Test with company
    $welcomeWithCompany = new \App\Mail\WelcomeMail(
        'John Smith',
        'john@example.com',
        'Acme Corporation',
        'ACME123'
    );
    
    // Test without company (individual)
    $welcomeIndividual = new \App\Mail\WelcomeMail(
        'Jane Doe',
        'jane@example.com'
    );
    
    return response()->json([
        'with_company' => 'Available at /test-welcome-email/company',
        'individual' => 'Available at /test-welcome-email/individual'
    ]);
});

Route::get('/test-welcome-email/company', function () {
    return view('emails.welcome', [
        'name' => 'John Smith',
        'email' => 'john@example.com',
        'company' => 'Acme Corporation',
        'company_code' => 'ACME123'
    ]);
});

Route::get('/test-welcome-email/individual', function () {
    return view('emails.welcome', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'company' => null,
        'company_code' => null
    ]);
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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/subscription.php';
