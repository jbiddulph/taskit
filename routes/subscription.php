<?php

use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// Subscription management routes (require authentication but NOT subscription access limits)
// Users must be able to access subscription page to upgrade when over limits
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::get('/subscription/success', [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::post('/subscription/cancel-subscription', [SubscriptionController::class, 'cancelSubscription'])->name('subscription.cancel-subscription');
    Route::post('/subscription/change-plan', function(\Illuminate\Http\Request $request) {
        error_log('=== ROUTE HIT BEFORE CONTROLLER ===');
        error_log('User ID: ' . auth()->id());
        error_log('Plan: ' . $request->input('plan'));
        
        try {
            error_log('About to call controller method...');
            $result = app(SubscriptionController::class)->changePlan($request);
            error_log('Controller method completed successfully');
            return $result;
        } catch (\Exception $e) {
            error_log('=== CONTROLLER EXCEPTION ===');
            error_log('Exception: ' . $e->getMessage());
            error_log('File: ' . $e->getFile() . ':' . $e->getLine());
            error_log('Stack trace: ' . $e->getTraceAsString());
            throw $e; // Re-throw to maintain normal error handling
        }
    })->name('subscription.change-plan');
    Route::post('/subscription/create-company-and-upgrade', [SubscriptionController::class, 'createCompanyAndUpgrade'])->name('subscription.create-company-and-upgrade');
    Route::post('/subscription/test-csrf', function() {
        \Log::info('=== TEST CSRF ROUTE HIT ===', [
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id()
        ]);
        return response()->json(['message' => 'CSRF test successful']);
    });
});

// Stripe webhook (no authentication required)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('stripe.webhook');
