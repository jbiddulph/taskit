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

// Debug route to test contact form logic
Route::get('/debug-contact', function () {
    try {
        $testData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Debug Test',
            'message' => 'This is a debug test message'
        ];
        
        // Test the Mailable creation
        $contactMail = new \App\Mail\ContactFormMail($testData);
        $confirmationMail = new \App\Mail\ContactConfirmationMail($testData['name'], $testData['email'], $testData['subject']);
        
        // Test sending
        \Illuminate\Support\Facades\Mail::to('john.mbiddulph@gmail.com')->send($contactMail);
        \Illuminate\Support\Facades\Mail::to($testData['email'])->send($confirmationMail);
        
        return response()->json(['status' => 'Debug emails sent successfully!']);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'Debug failed',
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString()
        ], 500);
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
