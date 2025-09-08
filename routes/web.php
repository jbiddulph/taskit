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

// Test route to send welcome email (temporary)
Route::get('/test-welcome-send', function () {
    try {
        // Test sending actual welcome email
        \Mail::to('john.mbiddulph@gmail.com')->send(new \App\Mail\WelcomeMail(
            'Test User',
            'john.mbiddulph@gmail.com',
            'Test Company',
            'TEST123'
        ));
        
        return response()->json(['status' => 'Welcome email sent successfully to john.mbiddulph@gmail.com']);
    } catch (\Exception $e) {
        return response()->json(['status' => 'Failed to send welcome email', 'error' => $e->getMessage()], 500);
    }
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
