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

        // Include scheduled change information
        $companyData = $company ? $company->toArray() : null;
        if ($company) {
            $companyData['pending_change'] = $company->getPendingSubscriptionChange();
            $companyData['effective_subscription_type'] = $company->getEffectiveSubscriptionType();
        }

        return Inertia::render('Subscription/Index', [
            'user' => $user,
            'company' => $companyData,
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
            'plan' => 'required|in:MIDI,MAXI,BUSINESS,LTD_SOLO,LTD_TEAM,LTD_AGENCY,LTD_BUSINESS',
            'billing_interval' => 'nullable|in:month,year',
        ]);

        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            return response()->json(['error' => 'No company found'], 400);
        }

        $plan = config("stripe.plans.{$request->plan}");
        $isLifetime = $plan['is_lifetime'] ?? false;

        // For lifetime deals, allow purchase even if they have a subscription
        // For recurring subscriptions, prevent creating new one if already active
        if (!$isLifetime && $company->stripe_subscription_id && $company->subscription_status === 'active') {
            return response()->json(['error' => 'Company already has an active subscription'], 400);
        }

        try {
            $billingInterval = $request->input('billing_interval', 'month');
            $session = $this->stripeService->createCheckoutSession(
                $company,
                $request->plan,
                $user->email,
                route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                route('subscription.cancel'),
                $billingInterval
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
            $isLifetime = ($session->metadata->is_lifetime ?? 'false') === 'true';

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

            // For lifetime deals (one-time payments), don't set subscription_id
            // For recurring subscriptions, set subscription_id and end date
            if ($isLifetime) {
                // Lifetime deals don't have subscriptions or end dates
                \Log::info('Lifetime deal payment successful', [
                    'company_id' => $company->id,
                    'plan_type' => $planType,
                    'session_id' => $sessionId
                ]);
            } elseif ($session->subscription) {
                // Recurring subscription - set subscription_id and end date
                $updateData['stripe_subscription_id'] = $session->subscription;
                
                // Get subscription details for end date
                $subscription = $this->stripeService->retrieveSubscription($session->subscription);
                if ($subscription && $subscription->current_period_end) {
                    $updateData['subscription_ends_at'] = now()->createFromTimestamp($subscription->current_period_end);
                } else {
                    // Fallback: estimate subscription end date as next month
                    $updateData['subscription_ends_at'] = now()->addMonth()->startOfDay();
                    \Log::warning('Subscription success callback missing period_end, using estimated date', [
                        'company_id' => $company->id,
                        'session_id' => $sessionId,
                        'subscription_id' => $session->subscription,
                        'estimated_date' => $updateData['subscription_ends_at']->toISOString()
                    ]);
                }
            }

            // Use save() instead of update() to ensure model events fire (which might trigger Supabase sync)
            $company->fill($updateData);
            $company->save();

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
     * Cancel subscription (schedule cancellation at period end)
     */
public function cancelSubscription()
    {
        $user = Auth::user();
        $company = $user->company;

        if (!$company || !$company->stripe_subscription_id) {
            return back()->withErrors(['subscription' => 'No active subscription found']);
        }

        try {
            // Cancel subscription at period end instead of immediately
            $subscription = $this->stripeService->cancelSubscriptionAtPeriodEnd($company->stripe_subscription_id);
            
            $periodEnd = null;
            
            // Try to get period end from Stripe subscription
            if ($subscription->current_period_end) {
                $periodEnd = \Carbon\Carbon::createFromTimestamp($subscription->current_period_end);
            }
            // Fallback to company's subscription_ends_at if available
            elseif ($company->subscription_ends_at) {
                $periodEnd = $company->subscription_ends_at;
                \Log::info('Using company subscription_ends_at as fallback for cancellation', [
                    'company_id' => $company->id,
                    'fallback_date' => $periodEnd->toISOString()
                ]);
            }
            // Final fallback: estimate next month from now
            else {
                $periodEnd = now()->addMonth()->startOfDay();
                \Log::warning('Using estimated period end date for cancellation', [
                    'company_id' => $company->id,
                    'estimated_date' => $periodEnd->toISOString(),
                    'subscription_status' => $subscription->status ?? 'unknown'
                ]);
            }
            
            // Schedule the change to FREE in our database
            $company->scheduleSubscriptionChange('FREE', $periodEnd);
            
            \Log::info('Scheduled subscription cancellation at period end', [
                'company_id' => $company->id,
                'current_plan' => $company->subscription_type,
                'scheduled_plan' => 'FREE',
                'change_date' => $periodEnd->toISOString(),
                'stripe_subscription_id' => $company->stripe_subscription_id,
                'stripe_period_end' => $subscription->current_period_end ?? 'null',
                'used_fallback' => !$subscription->current_period_end
            ]);

            $message = "Your subscription has been cancelled and will end on {$periodEnd->format('F j, Y')}. You'll continue to have access to your current {$company->subscription_type} plan features until then.";

            return back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error cancelling subscription', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['subscription' => $e->getMessage()]);
        }
    }

    /**
     * Change subscription plan
     */
    public function changePlan(Request $request)
    {
        error_log('=== CONTROLLER METHOD START ===');
        error_log('Request plan: ' . $request->input('plan'));
        
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
            'plan' => 'required|in:FREE,MIDI,MAXI,BUSINESS,LTD_SOLO,LTD_TEAM,LTD_AGENCY,LTD_BUSINESS',
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
            'requested_plan' => $request->plan,
            'has_existing_subscription' => !empty($company->stripe_subscription_id),
            'plan_comparison' => $company->subscription_type === $request->plan ? 'SAME' : 'DIFFERENT'
        ]);

        try {
            if ($request->plan === 'FREE') {
                // Schedule downgrade to FREE at the end of billing period
                if ($company->stripe_subscription_id) {
                    $subscription = $this->stripeService->cancelSubscriptionAtPeriodEnd($company->stripe_subscription_id);
                    
                    $periodEnd = null;
                    
                    // Try to get period end from Stripe subscription
                    if ($subscription->current_period_end) {
                        $periodEnd = \Carbon\Carbon::createFromTimestamp($subscription->current_period_end);
                    }
                    // Fallback to company's subscription_ends_at if available
                    elseif ($company->subscription_ends_at) {
                        $periodEnd = $company->subscription_ends_at;
                        \Log::info('Using company subscription_ends_at as fallback for FREE downgrade', [
                            'company_id' => $company->id,
                            'fallback_date' => $periodEnd->toISOString()
                        ]);
                    }
                    // Final fallback: estimate next month from now
                    else {
                        $periodEnd = now()->addMonth()->startOfDay();
                        \Log::warning('Using estimated period end date for FREE downgrade', [
                            'company_id' => $company->id,
                            'estimated_date' => $periodEnd->toISOString(),
                            'subscription_status' => $subscription->status ?? 'unknown'
                        ]);
                    }
                    
                    // Schedule the change in our database
                    $company->scheduleSubscriptionChange('FREE', $periodEnd);
                    
                    \Log::info('Scheduled FREE downgrade at period end', [
                        'company_id' => $company->id,
                        'current_plan' => $company->subscription_type,
                        'scheduled_plan' => 'FREE',
                        'change_date' => $periodEnd->toISOString(),
                        'stripe_subscription_id' => $company->stripe_subscription_id,
                        'stripe_period_end' => $subscription->current_period_end ?? 'null',
                        'used_fallback' => !$subscription->current_period_end
                    ]);
                    
                    $message = "Your subscription will be downgraded to FREE on {$periodEnd->format('F j, Y')}. You'll keep your current {$company->subscription_type} plan benefits until then.";
                } else {
                    // No active subscription, downgrade immediately
                    $company->subscription_type = 'FREE';
                    $company->save();
                    $message = 'Downgraded to FREE plan successfully';
                }

                if ($request->header('X-Inertia')) {
                    return back()->with('success', $message);
                }
                return response()->json(['message' => $message]);
            }

            if ($company->stripe_subscription_id) {
                // Check if this is a downgrade or upgrade
                $planHierarchy = [
                    'FREE' => 0, 
                    'MIDI' => 1, 
                    'MAXI' => 2, 
                    'BUSINESS' => 3,
                    'LTD_SOLO' => 1.5,
                    'LTD_TEAM' => 2.5,
                    'LTD_AGENCY' => 3.5,
                    'LTD_BUSINESS' => 4.5
                ];
                $currentLevel = $planHierarchy[$company->subscription_type] ?? 0;
                $targetLevel = $planHierarchy[$request->plan] ?? 0;
                $isDowngrade = $targetLevel < $currentLevel;
                
                if ($isDowngrade && $company->subscription_type === 'MAXI' && $request->plan === 'MIDI') {
                    // Schedule MAXI to MIDI downgrade at period end
                    $subscription = $this->stripeService->retrieveSubscription($company->stripe_subscription_id);
                    
                    $periodEnd = null;
                    
                    // Try to get period end from Stripe subscription
                    if ($subscription->current_period_end) {
                        $periodEnd = \Carbon\Carbon::createFromTimestamp($subscription->current_period_end);
                    }
                    // Fallback to company's subscription_ends_at if available
                    elseif ($company->subscription_ends_at) {
                        $periodEnd = $company->subscription_ends_at;
                        \Log::info('Using company subscription_ends_at as fallback', [
                            'company_id' => $company->id,
                            'fallback_date' => $periodEnd->toISOString()
                        ]);
                    }
                    // Final fallback: estimate next month from now
                    else {
                        $periodEnd = now()->addMonth()->startOfDay();
                        \Log::warning('Using estimated period end date as final fallback', [
                            'company_id' => $company->id,
                            'estimated_date' => $periodEnd->toISOString(),
                            'subscription_status' => $subscription->status ?? 'unknown'
                        ]);
                    }
                    
                    // Schedule the change in our database
                    $company->scheduleSubscriptionChange('MIDI', $periodEnd);
                    
                    \Log::info('Scheduled MIDI downgrade at period end', [
                        'company_id' => $company->id,
                        'current_plan' => $company->subscription_type,
                        'scheduled_plan' => 'MIDI',
                        'change_date' => $periodEnd->toISOString(),
                        'stripe_subscription_id' => $company->stripe_subscription_id,
                        'stripe_period_end' => $subscription->current_period_end ?? 'null',
                        'used_fallback' => !$subscription->current_period_end
                    ]);
                    
                    $message = "Your subscription will be downgraded to MIDI on {$periodEnd->format('F j, Y')}. You'll keep your current MAXI plan benefits until then.";
                } else {
                    // Immediate upgrade or same-tier change
                    $this->stripeService->updateSubscription($company->stripe_subscription_id, $request->plan);
                    $company->subscription_type = $request->plan;
                    $company->save();
                    
                    \Log::info('Immediately updated subscription', [
                        'company_id' => $company->id,
                        'old_plan' => $company->subscription_type,
                        'new_plan' => $request->plan,
                        'subscription_id' => $company->stripe_subscription_id
                    ]);
                    
                    $message = $isDowngrade ? 'Subscription downgraded successfully' : 'Subscription upgraded successfully';
                }
                
                if ($request->header('X-Inertia')) {
                    return back()->with('success', $message);
                }
                return response()->json(['message' => $message]);
            } else {
                // No existing subscription - create new checkout session
                \Log::info('Creating new checkout session for plan upgrade', [
                    'current_plan' => $company->subscription_type,
                    'target_plan' => $request->plan,
                    'company_id' => $company->id,
                    'has_customer_id' => !empty($company->stripe_customer_id)
                ]);
                
                try {
                    error_log('=== CALLING STRIPE SERVICE ===');
                    $billingInterval = $request->input('billing_interval', 'month');
                    $session = $this->stripeService->createCheckoutSession(
                        $company,
                        $request->plan,
                        $user->email,
                        route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                        route('subscription.cancel'),
                        $billingInterval
                    );
                    error_log('=== STRIPE SERVICE SUCCESS ===');
                } catch (\Exception $stripeException) {
                    error_log('=== STRIPE SERVICE FAILED ===');
                    error_log('Stripe error: ' . $stripeException->getMessage());
                    error_log('Stripe error file: ' . $stripeException->getFile() . ':' . $stripeException->getLine());
                    throw $stripeException; // Re-throw to trigger main exception handler
                }

                \Log::info('Checkout session created successfully', [
                    'session_id' => $session->id,
                    'session_url' => $session->url,
                    'company_id' => $company->id,
                    'plan' => $request->plan
                ]);

                // Handle both Inertia and JSON requests for checkout sessions
                \Log::info('=== RETURNING EXTERNAL REDIRECT ===', [
                    'redirect_url' => $session->url,
                    'session_id' => $session->id,
                    'using_inertia_location' => true
                ]);
                
                // For Stripe checkout, use Inertia's external location redirect
                // This works for both Inertia and regular requests
                \Log::info('About to redirect to Stripe checkout', [
                    'url' => $session->url,
                    'request_wants_json' => $request->wantsJson(),
                    'has_inertia_header' => $request->header('X-Inertia') ? true : false
                ]);
                return \Inertia\Inertia::location($session->url);
            }
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

    /**
     * Reactivate a cancelled subscription
     */
    public function reactivateSubscription()
    {
        $user = Auth::user();
        $company = $user->company;

        if (!$company || !$company->stripe_subscription_id) {
            return back()->withErrors(['subscription' => 'No active subscription found']);
        }

        if (!$company->hasPendingSubscriptionChange() || $company->scheduled_subscription_type !== 'FREE') {
            return back()->withErrors(['subscription' => 'No pending cancellation found']);
        }

        try {
            // Reactivate the subscription in Stripe (remove cancel_at_period_end)
            $subscription = $this->stripeService->reactivateSubscription($company->stripe_subscription_id);

            // Clear the scheduled change in our database
            $company->scheduled_subscription_type = null;
            $company->scheduled_change_date = null;
            $company->save();

            \Log::info('Subscription reactivated successfully', [
                'company_id' => $company->id,
                'subscription_id' => $company->stripe_subscription_id,
                'plan' => $company->subscription_type
            ]);

            return back()->with('success', 'Your subscription has been reactivated successfully! The scheduled cancellation has been cancelled.');
        } catch (\Exception $e) {
            \Log::error('Error reactivating subscription', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['subscription' => 'Failed to reactivate subscription: ' . $e->getMessage()]);
        }
    }

    /**
     * Create company for individual user and proceed with subscription upgrade
     */
    public function createCompanyAndUpgrade(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'target_plan' => 'required|in:MIDI,MAXI,BUSINESS,LTD_SOLO,LTD_TEAM,LTD_AGENCY,LTD_BUSINESS',
        ]);

        $user = Auth::user();

        // Check if user already has a company
        if ($user->company_id) {
            return back()->withErrors(['company_name' => 'User already has a company']);
        }

        try {
            // Create the company
            $company = \App\Models\Company::create([
                'name' => $request->company_name,
                'code' => \App\Models\Company::generateUniqueCode(),
                'subscription_type' => 'FREE',
                'subscription_status' => 'active',
            ]);

            // Link user to the company
            $user->update(['company_id' => $company->id]);

            \Log::info('Company created for individual user', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'company_name' => $company->name,
                'company_code' => $company->code,
                'target_plan' => $request->target_plan
            ]);

            // Now create checkout session for the target plan
            $billingInterval = $request->input('billing_interval', 'month');
            $session = $this->stripeService->createCheckoutSession(
                $company,
                $request->target_plan,
                $user->email,
                route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                route('subscription.cancel'),
                $billingInterval
            );

            \Log::info('Checkout session created for new company', [
                'company_id' => $company->id,
                'session_id' => $session->id,
                'target_plan' => $request->target_plan
            ]);

            // Return redirect URL for Stripe checkout
            return back()->with([
                'success' => 'Company created successfully',
                'redirect_url' => $session->url
            ]);

        } catch (\Exception $e) {
            \Log::error('Company creation and upgrade failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['company_name' => 'Failed to create company: ' . $e->getMessage()]);
        }
    }
}