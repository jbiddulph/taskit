<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        protected StripeService $stripeService
    ) {}

    /**
     * Show subscription management page
     */
    public function index(): Response
    {
        $user = Auth::user();
        $company = $user->company;

        return Inertia::render('Subscription/Index', [
            'user' => $user,
            'company' => $company,
            'plans' => config('stripe.plans'),
            'stripePublicKey' => config('stripe.public_key'),
        ]);
    }

    /**
     * Create a checkout session for subscription
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'required|in:MIDI,MAXI',
        ]);

        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            return response()->json(['error' => 'No company found'], 400);
        }

        // If company already has an active subscription, prevent creating new one
        if ($company->stripe_subscription_id && $company->subscription_status === 'active') {
            return response()->json(['error' => 'Company already has an active subscription'], 400);
        }

        try {
            $session = $this->stripeService->createCheckoutSession(
                $company,
                $request->plan,
                $user->email,
                route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                route('subscription.cancel')
            );

            return response()->json(['checkout_url' => $session->url]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle successful payment
     */
    public function success(Request $request): RedirectResponse
    {
        $sessionId = $request->get('session_id');
        
        if (!$sessionId) {
            return redirect()->route('dashboard')->with('error', 'Invalid session');
        }

        // The webhook will handle updating the company's subscription details
        return redirect()->route('dashboard')->with('success', 'Subscription activated successfully!');
    }

    /**
     * Handle cancelled payment
     */
    public function cancel(): RedirectResponse
    {
        return redirect()->route('dashboard')->with('info', 'Subscription cancelled');
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription()
    {
        $user = Auth::user();
        $company = $user->company;

        if (!$company || !$company->stripe_subscription_id) {
            return back()->withErrors(['subscription' => 'No active subscription found']);
        }

        try {
            $this->stripeService->cancelSubscription($company->stripe_subscription_id);
            
            $company->update([
                'subscription_status' => 'canceled',
                'subscription_type' => 'FREE',
            ]);

            return back()->with('success', 'Subscription cancelled successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['subscription' => $e->getMessage()]);
        }
    }

    /**
     * Change subscription plan
     */
    public function changePlan(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:FREE,MIDI,MAXI',
        ]);

        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            return response()->json(['message' => 'No company found'], 400);
        }

        try {
            if ($request->plan === 'FREE') {
                // Downgrade to FREE - cancel subscription
                if ($company->stripe_subscription_id) {
                    $this->stripeService->cancelSubscription($company->stripe_subscription_id);
                }
                
                $company->update([
                    'subscription_type' => 'FREE',
                    'subscription_status' => 'active',
                    'stripe_subscription_id' => null,
                    'subscription_ends_at' => null,
                ]);

                return response()->json(['message' => 'Downgraded to FREE plan successfully']);
            }

            if ($company->stripe_subscription_id) {
                // Update existing subscription
                $this->stripeService->updateSubscription($company->stripe_subscription_id, $request->plan);
                $company->update(['subscription_type' => $request->plan]);
                
                return response()->json(['message' => 'Subscription updated successfully']);
            } else {
                // Need to create new subscription
                $session = $this->stripeService->createCheckoutSession(
                    $company,
                    $request->plan,
                    $user->email,
                    route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    route('subscription.cancel')
                );

                        // Force JSON response for subscription redirects to avoid CORS issues
        return response()->json(['redirect_url' => $session->url]);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}