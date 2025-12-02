<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request): Response
    {
        Stripe::setApiKey(config('stripe.secret_key'));
        
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('stripe.webhook_secret');
        
        Log::info('Stripe webhook received', [
            'has_signature' => !empty($sigHeader),
            'has_secret' => !empty($endpointSecret),
            'payload_length' => strlen($payload)
        ]);

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid Stripe webhook payload', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid Stripe webhook signature', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event->data->object);
                break;
                
            case 'payment_intent.succeeded':
                // Handle one-time payments (lifetime deals) that might not trigger checkout.session.completed immediately
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;
                
            case 'customer.subscription.created':
                $this->handleSubscriptionCreated($event->data->object);
                break;
                
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;
                
            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;
                
            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($event->data->object);
                break;
                
            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($event->data->object);
                break;
                
            default:
                Log::info('Received unknown Stripe event type', ['type' => $event->type]);
        }

        return response('Webhook handled', 200);
    }

    private function handleCheckoutSessionCompleted($session): void
    {
        $companyId = $session->metadata->company_id ?? null;
        $planType = $session->metadata->plan_type ?? null;
        $isLifetime = ($session->metadata->is_lifetime ?? 'false') === 'true';

        if (!$companyId || !$planType) {
            Log::error('Missing metadata in checkout session', ['session_id' => $session->id]);
            return;
        }

        $company = Company::find($companyId);
        if (!$company) {
            Log::error('Company not found for checkout session', ['company_id' => $companyId]);
            return;
        }

        $updateData = [
            'stripe_customer_id' => $session->customer,
            'subscription_type' => $planType,
            'subscription_status' => 'active',
        ];

        // For lifetime deals (one-time payments), we don't set subscription_id
        // For recurring subscriptions, the subscription will be created separately
        if ($isLifetime) {
            // Lifetime deals don't have subscriptions, just set the plan type
            Log::info('Lifetime deal checkout completed', [
                'company_id' => $companyId,
                'plan_type' => $planType,
                'session_id' => $session->id
            ]);
        } else {
            // For subscriptions, the subscription will be created via subscription.created event
            // But we can set the subscription_id if it's available
            if ($session->subscription) {
                $updateData['stripe_subscription_id'] = $session->subscription;
                
                // Get subscription details for end date
                try {
                    $subscription = \Stripe\Subscription::retrieve($session->subscription);
                    if ($subscription->current_period_end) {
                        $updateData['subscription_ends_at'] = now()->createFromTimestamp($subscription->current_period_end);
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not retrieve subscription details', [
                        'subscription_id' => $session->subscription,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Use save() instead of update() to ensure model events fire (which might trigger Supabase sync)
        $company->fill($updateData);
        $company->save();

        Log::info('Checkout session completed', [
            'company_id' => $companyId,
            'plan_type' => $planType,
            'is_lifetime' => $isLifetime,
            'session_id' => $session->id
        ]);
    }

    /**
     * Handle payment_intent.succeeded for one-time payments (lifetime deals)
     * This ensures one-time payments are processed even if checkout.session.completed doesn't fire
     * Note: Payment intents from checkout sessions may not have metadata, so we try to get it from the session
     */
    private function handlePaymentIntentSucceeded($paymentIntent): void
    {
        // Try to get metadata from payment intent first
        $companyId = $paymentIntent->metadata->company_id ?? null;
        $planType = $paymentIntent->metadata->plan_type ?? null;
        $isLifetime = ($paymentIntent->metadata->is_lifetime ?? 'false') === 'true';

        // If metadata not on payment intent, try to get from checkout session
        if (!$companyId && isset($paymentIntent->metadata->checkout_session_id)) {
            try {
                $session = \Stripe\Checkout\Session::retrieve($paymentIntent->metadata->checkout_session_id);
                $companyId = $session->metadata->company_id ?? null;
                $planType = $session->metadata->plan_type ?? null;
                $isLifetime = ($session->metadata->is_lifetime ?? 'false') === 'true';
            } catch (\Exception $e) {
                Log::warning('Could not retrieve checkout session from payment intent', [
                    'payment_intent_id' => $paymentIntent->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Only process if this is from a checkout session (has metadata)
        if (!$companyId || !$planType) {
            return; // Not a subscription-related payment
        }

        // Only handle lifetime deals here (subscriptions are handled via subscription events)
        if (!$isLifetime) {
            return;
        }

        $company = Company::find($companyId);
        if (!$company) {
            Log::error('Company not found for payment intent', ['company_id' => $companyId]);
            return;
        }

        // Update company for lifetime deal - ensure subscription_type and subscription_status are set
        $company->fill([
            'stripe_customer_id' => $paymentIntent->customer ?? $company->stripe_customer_id,
            'subscription_type' => $planType, // This is critical - sets LTD_SOLO, LTD_TEAM, etc.
            'subscription_status' => 'active', // Active status for lifetime deals
        ]);
        $company->save();

        Log::info('Payment intent succeeded for lifetime deal', [
            'company_id' => $companyId,
            'plan_type' => $planType,
            'payment_intent_id' => $paymentIntent->id,
            'subscription_type_set' => $company->subscription_type,
            'subscription_status_set' => $company->subscription_status
        ]);
    }

    private function handleSubscriptionCreated($subscription): void
    {
        $companyId = $subscription->metadata->company_id ?? null;
        $planType = $subscription->metadata->plan_type ?? null;

        if (!$companyId) {
            Log::error('Missing company_id in subscription metadata', ['subscription_id' => $subscription->id]);
            return;
        }

        $company = Company::find($companyId);
        if (!$company) {
            Log::error('Company not found for subscription', ['company_id' => $companyId]);
            return;
        }

        // Determine subscription end date
        $subscriptionEndsAt = null;
        if ($subscription->current_period_end) {
            $subscriptionEndsAt = now()->createFromTimestamp($subscription->current_period_end);
        } else {
            // Fallback: estimate next month from now for new subscriptions
            $subscriptionEndsAt = now()->addMonth()->startOfDay();
            Log::warning('Subscription missing current_period_end, using estimated date', [
                'subscription_id' => $subscription->id,
                'company_id' => $companyId,
                'estimated_date' => $subscriptionEndsAt->toISOString()
            ]);
        }

        // Use save() instead of update() to ensure model events fire
        $company->fill([
            'stripe_subscription_id' => $subscription->id,
            'subscription_type' => $planType ?? $company->subscription_type,
            'subscription_status' => $subscription->status,
            'subscription_ends_at' => $subscriptionEndsAt,
        ]);
        $company->save();

        Log::info('Subscription created', [
            'company_id' => $companyId,
            'subscription_id' => $subscription->id,
            'status' => $subscription->status
        ]);
    }

    private function handleSubscriptionUpdated($subscription): void
    {
        $company = Company::where('stripe_subscription_id', $subscription->id)->first();
        
        if (!$company) {
            Log::error('Company not found for subscription update', ['subscription_id' => $subscription->id]);
            return;
        }

        // Detect plan changes by checking the current price against our configured plans
        $currentPrice = $subscription->items->data[0]->price ?? null;
        $newPlanType = null;
        
        if ($currentPrice) {
            $plans = config('stripe.plans');
            foreach ($plans as $planKey => $planData) {
                if (isset($planData['stripe_price_id']) && $planData['stripe_price_id'] === $currentPrice->id) {
                    $newPlanType = $planKey;
                    break;
                }
            }
        }

        // Determine subscription end date with fallback
        $subscriptionEndsAt = null;
        if ($subscription->current_period_end) {
            $subscriptionEndsAt = now()->createFromTimestamp($subscription->current_period_end);
        } elseif ($company->subscription_ends_at) {
            // Keep existing end date if Stripe doesn't provide one
            $subscriptionEndsAt = $company->subscription_ends_at;
            Log::info('Keeping existing subscription_ends_at date', [
                'subscription_id' => $subscription->id,
                'company_id' => $company->id,
                'existing_date' => $subscriptionEndsAt->toISOString()
            ]);
        } else {
            // Final fallback: estimate next month
            $subscriptionEndsAt = now()->addMonth()->startOfDay();
            Log::warning('Subscription update missing period end, using estimated date', [
                'subscription_id' => $subscription->id,
                'company_id' => $company->id,
                'estimated_date' => $subscriptionEndsAt->toISOString()
            ]);
        }

        $updateData = [
            'subscription_status' => $subscription->status,
            'subscription_ends_at' => $subscriptionEndsAt,
        ];

        // If plan changed and it matches a scheduled change, apply it
        if ($newPlanType && $newPlanType !== $company->subscription_type) {
            if ($company->scheduled_subscription_type === $newPlanType && $company->scheduled_change_date) {
                Log::info('Applying scheduled plan change via webhook', [
                    'company_id' => $company->id,
                    'from_plan' => $company->subscription_type,
                    'to_plan' => $newPlanType,
                    'scheduled_date' => $company->scheduled_change_date->toISOString()
                ]);
                
                // Apply the scheduled change
                $company->applyScheduledChange();
                $updateData['subscription_type'] = $newPlanType;
            } else {
                // Immediate plan change (upgrade)
                $updateData['subscription_type'] = $newPlanType;
                Log::info('Immediate plan change detected', [
                    'company_id' => $company->id,
                    'old_plan' => $company->subscription_type,
                    'new_plan' => $newPlanType
                ]);
            }
        }

        // Use save() instead of update() to ensure model events fire
        $company->fill($updateData);
        $company->save();

        Log::info('Subscription updated via webhook', [
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'status' => $subscription->status,
            'plan_changed' => $newPlanType ? ($newPlanType !== $company->getOriginal('subscription_type')) : false,
            'new_plan' => $newPlanType
        ]);
    }

    private function handleSubscriptionDeleted($subscription): void
    {
        $company = Company::where('stripe_subscription_id', $subscription->id)->first();
        
        if (!$company) {
            Log::error('Company not found for subscription deletion', ['subscription_id' => $subscription->id]);
            return;
        }

        Log::info('Processing subscription deletion webhook', [
            'company_id' => $company->id,
            'current_subscription_type' => $company->subscription_type,
            'scheduled_subscription_type' => $company->scheduled_subscription_type,
            'subscription_id' => $subscription->id
        ]);

        // Check if this is a scheduled downgrade to FREE
        if ($company->scheduled_subscription_type === 'FREE' && $company->scheduled_change_date) {
            Log::info('Applying scheduled FREE downgrade via webhook', [
                'company_id' => $company->id,
                'from_plan' => $company->subscription_type,
                'to_plan' => 'FREE',
                'scheduled_date' => $company->scheduled_change_date->toISOString()
            ]);
            
            // Apply the scheduled change
            $company->applyScheduledChange();
        }

        // Clear ALL Stripe-related data so company can resubscribe later
        $company->update([
            'subscription_type' => $company->scheduled_subscription_type ?: 'FREE',
            'subscription_status' => 'active',        // Set to active for FREE plan
            'stripe_customer_id' => null,            // Clear customer ID
            'stripe_subscription_id' => null,        // Clear subscription ID
            'subscription_ends_at' => null,          // Clear end date
            'scheduled_subscription_type' => null,   // Clear scheduled change
            'scheduled_change_date' => null,         // Clear scheduled date
        ]);

        Log::info('Successfully processed subscription deletion via webhook', [
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'new_subscription_type' => $company->subscription_type
        ]);
    }

    private function handleInvoicePaymentSucceeded($invoice): void
    {
        if (!$invoice->subscription) {
            return;
        }

        $company = Company::where('stripe_subscription_id', $invoice->subscription)->first();
        
        if (!$company) {
            Log::error('Company not found for invoice payment', ['subscription_id' => $invoice->subscription]);
            return;
        }

        // Update subscription status to active if it was past due
        if ($company->subscription_status === 'past_due') {
            $company->update(['subscription_status' => 'active']);
        }

        Log::info('Invoice payment succeeded', [
            'company_id' => $company->id,
            'subscription_id' => $invoice->subscription,
            'invoice_id' => $invoice->id
        ]);
    }

    private function handleInvoicePaymentFailed($invoice): void
    {
        if (!$invoice->subscription) {
            return;
        }

        $company = Company::where('stripe_subscription_id', $invoice->subscription)->first();
        
        if (!$company) {
            Log::error('Company not found for failed invoice payment', ['subscription_id' => $invoice->subscription]);
            return;
        }

        $company->update(['subscription_status' => 'past_due']);

        Log::warning('Invoice payment failed', [
            'company_id' => $company->id,
            'subscription_id' => $invoice->subscription,
            'invoice_id' => $invoice->id
        ]);
    }
}