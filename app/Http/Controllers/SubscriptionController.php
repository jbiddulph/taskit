<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Customer;
use Stripe\Subscription;

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

        try {
            // Retrieve the checkout session from Stripe
            $session = $this->stripeService->retrieveCheckoutSession($sessionId);
            
            if (!$session) {
                return redirect()->route('dashboard')->with('error', 'Invalid session');
            }

            // Get company info from session metadata
            $companyId = $session->metadata->company_id ?? null;
            $planType = $session->metadata->plan_type ?? null;

            if (!$companyId || !$planType) {
                \Log::error('Missing metadata in checkout session', ['session_id' => $sessionId]);
                return redirect()->route('dashboard')->with('error', 'Session metadata missing');
            }

            $user = Auth::user();
            $company = $user->company;

            if (!$company || $company->id != $companyId) {
                \Log::error('Company mismatch in checkout session', [
                    'session_company_id' => $companyId,
                    'user_company_id' => $company->id ?? 'null'
                ]);
                return redirect()->route('dashboard')->with('error', 'Company mismatch');
            }

            // Update company with subscription details
            $updateData = [
                'stripe_customer_id' => $session->customer,
                'subscription_type' => $planType,
                'subscription_status' => 'active',
            ];

            // If there's a subscription ID, save it
            if ($session->subscription) {
                $updateData['stripe_subscription_id'] = $session->subscription;
                
                // Get subscription details for end date
                $subscription = $this->stripeService->retrieveSubscription($session->subscription);
                if ($subscription && $subscription->current_period_end) {
                    $updateData['subscription_ends_at'] = now()->createFromTimestamp($subscription->current_period_end);
                }
            }

            $company->update($updateData);

            \Log::info('Subscription activated via success callback', [
                'company_id' => $company->id,
                'session_id' => $sessionId,
                'subscription_id' => $session->subscription ?? 'none',
                'plan_type' => $planType
            ]);

            return redirect()->route('dashboard')->with('success', 'Subscription activated successfully!');
        } catch (\Exception $e) {
            \Log::error('Error processing subscription success', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return redirect()->route('dashboard')->with('error', 'Error activating subscription. Please contact support.');
        }
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
        \Log::info('=== SUBSCRIPTION CHANGE PLAN CONTROLLER HIT ===', [
            'timestamp' => now()->toISOString(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'plan' => $request->plan,
            'user_id' => auth()->id(),
            'X-Inertia' => $request->header('X-Inertia'),
            'X-Requested-With' => $request->header('X-Requested-With'),
            'wantsJson' => $request->wantsJson(),
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept')
        ]);

        $request->validate([
            'plan' => 'required|in:FREE,MIDI,MAXI',
        ]);

        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            if ($request->header('X-Inertia')) {
                return back()->withErrors(['plan' => 'No company found']);
            }
            return response()->json(['message' => 'No company found'], 400);
        }

        \Log::info('changePlan company details', [
            'company_id' => $company->id,
            'current_subscription_type' => $company->subscription_type,
            'stripe_customer_id' => $company->stripe_customer_id,
            'stripe_subscription_id' => $company->stripe_subscription_id,
            'subscription_status' => $company->subscription_status,
            'requested_plan' => $request->plan
        ]);

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

                if ($request->header('X-Inertia')) {
                    return back()->with('success', 'Downgraded to FREE plan successfully');
                }
                return response()->json(['message' => 'Downgraded to FREE plan successfully']);
            }

            if ($company->stripe_subscription_id) {
                // Update existing subscription
                \Log::info('Updating existing subscription', [
                    'subscription_id' => $company->stripe_subscription_id,
                    'new_plan' => $request->plan
                ]);
                
                $this->stripeService->updateSubscription($company->stripe_subscription_id, $request->plan);
                $company->update(['subscription_type' => $request->plan]);
                
                if ($request->header('X-Inertia')) {
                    return back()->with('success', 'Subscription updated successfully');
                }
                return response()->json(['message' => 'Subscription updated successfully']);
            } elseif ($company->stripe_customer_id) {
                // Company has customer ID but no subscription ID - check Stripe for existing subscriptions
                \Log::info('Company has customer ID but no subscription ID, checking Stripe', [
                    'customer_id' => $company->stripe_customer_id
                ]);
                
                try {
                    // Set Stripe API key
                    \Stripe\Stripe::setApiKey(config('stripe.secret_key'));
                    
                    $customer = Customer::retrieve($company->stripe_customer_id);
                    $subscriptions = Subscription::all(['customer' => $company->stripe_customer_id, 'status' => 'active']);
                    
                    if ($subscriptions->data && count($subscriptions->data) > 0) {
                        $activeSubscription = $subscriptions->data[0];
                        \Log::info('Found active subscription for customer', [
                            'subscription_id' => $activeSubscription->id
                        ]);
                        
                        // Update the company with the found subscription ID
                        $company->update(['stripe_subscription_id' => $activeSubscription->id]);
                        
                        // Now update the subscription
                        $this->stripeService->updateSubscription($activeSubscription->id, $request->plan);
                        $company->update(['subscription_type' => $request->plan]);
                        
                        if ($request->header('X-Inertia')) {
                            return back()->with('success', 'Subscription updated successfully');
                        }
                        return response()->json(['message' => 'Subscription updated successfully']);
                    }
                } catch (\Exception $e) {
                    \Log::error('Error checking existing subscriptions', ['error' => $e->getMessage()]);
                }
                
                // If no active subscription found, fall through to create new one
                \Log::info('No active subscription found, will create new checkout session');
            }
            
            // Create new subscription (either no customer ID or no active subscription found)
            \Log::info('Creating new subscription checkout session', [
                'company_id' => $company->id,
                'plan' => $request->plan,
                'has_customer_id' => !empty($company->stripe_customer_id)
            ]);
            
            $session = $this->stripeService->createCheckoutSession(
                $company,
                $request->plan,
                $user->email,
                route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                route('subscription.cancel')
            );

            \Log::info('Checkout session created successfully', [
                'session_id' => $session->id,
                'session_url' => $session->url,
                'company_id' => $company->id,
                'plan' => $request->plan
            ]);

            // Handle both Inertia and JSON requests for checkout sessions
            if ($request->header('X-Inertia')) {
                \Log::info('=== RETURNING INERTIA RESPONSE ===', [
                    'redirect_url' => $session->url,
                    'session_id' => $session->id,
                    'flash_data_being_set' => 'redirect_url'
                ]);
                
                $response = back()->with('redirect_url', $session->url);
                \Log::info('Inertia response created', ['response_type' => get_class($response)]);
                return $response;
            }
            
            \Log::info('=== RETURNING JSON RESPONSE ===', ['redirect_url' => $session->url]);
            // Fallback for direct JSON requests
            return response()->json(['redirect_url' => $session->url]);
        } catch (\Exception $e) {
            \Log::error('=== EXCEPTION IN CHANGE PLAN ===', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            
            if ($request->header('X-Inertia')) {
                \Log::info('Returning Inertia error response');
                return back()->withErrors(['plan' => $e->getMessage()]);
            }
            
            \Log::info('Returning JSON error response');
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}