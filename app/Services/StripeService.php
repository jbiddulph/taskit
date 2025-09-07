<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\Price;
use Stripe\Product;
use Stripe\Checkout\Session;
use App\Models\Company;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret_key'));
    }

    /**
     * Create or retrieve a Stripe customer for a company
     */
    public function createOrGetCustomer(Company $company, string $email): Customer
    {
        if ($company->stripe_customer_id) {
            try {
                return Customer::retrieve($company->stripe_customer_id);
            } catch (\Exception $e) {
                // Customer doesn't exist, create new one
            }
        }

        $customer = Customer::create([
            'email' => $email,
            'name' => $company->name,
            'metadata' => [
                'company_id' => $company->id,
                'company_code' => $company->code,
            ]
        ]);

        $company->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    /**
     * Create a checkout session for subscription
     */
    public function createCheckoutSession(Company $company, string $planType, string $email, string $successUrl, string $cancelUrl): Session
    {
        $customer = $this->createOrGetCustomer($company, $email);
        $plan = config("stripe.plans.{$planType}");

        if (!$plan || !$plan['stripe_price_id']) {
            throw new \InvalidArgumentException("Invalid plan type: {$planType}");
        }

        \Log::info('Creating Stripe checkout session', [
            'company_id' => $company->id,
            'plan_type' => $planType,
            'price_id' => $plan['stripe_price_id'],
            'customer_id' => $customer->id,
            'email' => $email
        ]);

        try {
            $session = Session::create([
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price' => $plan['stripe_price_id'],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'subscription',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'billing_address_collection' => 'required',
                'customer_update' => [
                    'address' => 'auto',
                    'name' => 'auto'
                ],
                'metadata' => [
                    'company_id' => $company->id,
                    'plan_type' => $planType,
                ],
                'subscription_data' => [
                    'metadata' => [
                        'company_id' => $company->id,
                        'plan_type' => $planType,
                    ]
                ],
                'payment_method_collection' => 'always'
            ]);

            \Log::info('Stripe checkout session created successfully', [
                'session_id' => $session->id,
                'url' => $session->url,
                'company_id' => $company->id
            ]);

            return $session;
        } catch (\Exception $e) {
            \Log::error('Failed to create Stripe checkout session', [
                'error' => $e->getMessage(),
                'company_id' => $company->id,
                'plan_type' => $planType,
                'price_id' => $plan['stripe_price_id']
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve a checkout session
     */
    public function retrieveCheckoutSession(string $sessionId): Session
    {
        return Session::retrieve($sessionId);
    }

    /**
     * Retrieve a subscription
     */
    public function retrieveSubscription(string $subscriptionId): Subscription
    {
        return Subscription::retrieve($subscriptionId);
    }

    /**
     * Cancel a subscription immediately
     */
    public function cancelSubscription(string $subscriptionId): Subscription
    {
        return Subscription::retrieve($subscriptionId)->cancel();
    }

    /**
     * Cancel a subscription at the end of the current period
     */
    public function cancelSubscriptionAtPeriodEnd(string $subscriptionId): Subscription
    {
        return Subscription::update($subscriptionId, [
            'cancel_at_period_end' => true
        ]);
    }

    /**
     * Update subscription to new plan immediately
     */
    public function updateSubscription(string $subscriptionId, string $newPlanType): Subscription
    {
        $plan = config("stripe.plans.{$newPlanType}");
        
        if (!$plan || !$plan['stripe_price_id']) {
            throw new \InvalidArgumentException("Invalid plan type: {$newPlanType}");
        }

        $subscription = Subscription::retrieve($subscriptionId);
        
        return Subscription::update($subscriptionId, [
            'items' => [
                [
                    'id' => $subscription->items->data[0]->id,
                    'price' => $plan['stripe_price_id'],
                ]
            ]
        ]);
    }

    /**
     * Schedule a subscription plan change at the end of the current period
     */
    public function scheduleSubscriptionChange(string $subscriptionId, string $newPlanType): Subscription
    {
        $plan = config("stripe.plans.{$newPlanType}");
        
        if (!$plan || !$plan['stripe_price_id']) {
            throw new \InvalidArgumentException("Invalid plan type: {$newPlanType}");
        }

        $subscription = Subscription::retrieve($subscriptionId);
        
        // For downgrades, we schedule the change at period end
        return Subscription::update($subscriptionId, [
            'items' => [
                [
                    'id' => $subscription->items->data[0]->id,
                    'price' => $plan['stripe_price_id'],
                ]
            ],
            'proration_behavior' => 'none', // Don't prorate for downgrades
            'billing_cycle_anchor' => 'unchanged' // Keep the same billing cycle
        ]);
    }

    /**
     * Get subscription details
     */
    public function getSubscription(string $subscriptionId): Subscription
    {
        return Subscription::retrieve($subscriptionId);
    }

    /**
     * Reactivate a cancelled subscription (remove cancel_at_period_end)
     */
    public function reactivateSubscription(string $subscriptionId): Subscription
    {
        return Subscription::update($subscriptionId, [
            'cancel_at_period_end' => false
        ]);
    }

    /**
     * Create products and prices in Stripe (run once in setup)
     */
    public function createProducts(): array
    {
        $results = [];
        $plans = config('stripe.plans');

        foreach ($plans as $planType => $planData) {
            if ($planType === 'FREE') {
                continue; // Skip FREE plan as it doesn't need Stripe
            }

            // Create product
            $product = Product::create([
                'name' => $planData['name'],
                'description' => "TaskIT {$planData['name']} - " . implode(', ', $planData['features']),
                'metadata' => [
                    'plan_type' => $planType
                ]
            ]);

            // Create price
            $price = Price::create([
                'product' => $product->id,
                'unit_amount' => $planData['price'],
                'currency' => $planData['currency'],
                'recurring' => [
                    'interval' => 'month'
                ],
                'metadata' => [
                    'plan_type' => $planType
                ]
            ]);

            $results[$planType] = [
                'product_id' => $product->id,
                'price_id' => $price->id
            ];
        }

        return $results;
    }
}
