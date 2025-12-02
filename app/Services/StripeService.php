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
                \Log::info('Attempting to retrieve existing Stripe customer', [
                    'customer_id' => $company->stripe_customer_id,
                    'company_id' => $company->id
                ]);
                return Customer::retrieve($company->stripe_customer_id);
            } catch (\Exception $e) {
                \Log::warning('Existing Stripe customer not found, clearing ID and creating new one', [
                    'old_customer_id' => $company->stripe_customer_id,
                    'company_id' => $company->id,
                    'error' => $e->getMessage()
                ]);
                // Clear the invalid customer ID
                $company->update(['stripe_customer_id' => null]);
            }
        }

        \Log::info('Creating new Stripe customer', [
            'company_id' => $company->id,
            'email' => $email,
            'company_name' => $company->name
        ]);

        $customer = Customer::create([
            'email' => $email,
            'name' => $company->name,
            'metadata' => [
                'company_id' => $company->id,
                'company_code' => $company->code,
            ]
        ]);

        $company->update(['stripe_customer_id' => $customer->id]);

        \Log::info('New Stripe customer created successfully', [
            'customer_id' => $customer->id,
            'company_id' => $company->id
        ]);

        return $customer;
    }

    /**
     * Create a checkout session for subscription or one-time payment
     */
    public function createCheckoutSession(Company $company, string $planType, string $email, string $successUrl, string $cancelUrl, string $billingInterval = 'month'): Session
    {
        $customer = $this->createOrGetCustomer($company, $email);
        $plan = config("stripe.plans.{$planType}");

        if (!$plan) {
            throw new \InvalidArgumentException("Invalid plan type: {$planType}");
        }

        // Check if this is a lifetime deal (one-time payment)
        $isLifetime = $plan['is_lifetime'] ?? false;
        
        // Determine which price ID to use
        $priceId = null;
        if ($isLifetime) {
            $priceId = $plan['stripe_price_id'] ?? null;
        } else {
            // For recurring subscriptions, check if yearly or monthly
            if ($billingInterval === 'year' && isset($plan['stripe_price_id_yearly'])) {
                $priceId = $plan['stripe_price_id_yearly'];
            } else {
                $priceId = $plan['stripe_price_id'] ?? null;
            }
        }

        if (!$priceId) {
            throw new \InvalidArgumentException("No Stripe price ID found for plan type: {$planType} with interval: {$billingInterval}");
        }

        \Log::info('Creating Stripe checkout session', [
            'company_id' => $company->id,
            'plan_type' => $planType,
            'price_id' => $priceId,
            'billing_interval' => $billingInterval,
            'is_lifetime' => $isLifetime,
            'customer_id' => $customer->id,
            'email' => $email
        ]);

        try {
            $sessionParams = [
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price' => $priceId,
                        'quantity' => 1,
                    ]
                ],
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
                    'billing_interval' => $billingInterval,
                    'is_lifetime' => $isLifetime ? 'true' : 'false',
                ]
            ];

            // Set mode based on whether it's a subscription or one-time payment
            if ($isLifetime) {
                $sessionParams['mode'] = 'payment';
                // For one-time payments, don't set payment_method_collection
                // It's only allowed for recurring subscriptions
            } else {
                $sessionParams['mode'] = 'subscription';
                $sessionParams['payment_method_collection'] = 'always';
                $sessionParams['subscription_data'] = [
                    'metadata' => [
                        'company_id' => $company->id,
                        'plan_type' => $planType,
                        'billing_interval' => $billingInterval,
                    ]
                ];
            }

            $session = Session::create($sessionParams);

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

            $isLifetime = $planData['is_lifetime'] ?? false;

            // Create product
            $product = Product::create([
                'name' => $planData['name'],
                'description' => "TaskIT {$planData['name']} - " . implode(', ', $planData['features']),
                'metadata' => [
                    'plan_type' => $planType
                ]
            ]);

            $planResults = [
                'product_id' => $product->id,
            ];

            if ($isLifetime) {
                // Create one-time payment price for lifetime deals
                $price = Price::create([
                    'product' => $product->id,
                    'unit_amount' => $planData['price'],
                    'currency' => $planData['currency'],
                    'metadata' => [
                        'plan_type' => $planType,
                        'is_lifetime' => 'true'
                    ]
                ]);
                $planResults['price_id'] = $price->id;
            } else {
                // Create monthly recurring price
                $monthlyPrice = Price::create([
                    'product' => $product->id,
                    'unit_amount' => $planData['price'],
                    'currency' => $planData['currency'],
                    'recurring' => [
                        'interval' => 'month'
                    ],
                    'metadata' => [
                        'plan_type' => $planType,
                        'billing_interval' => 'month'
                    ]
                ]);
                $planResults['price_id'] = $monthlyPrice->id;

                // Create yearly recurring price if it exists
                if (isset($planData['price_yearly'])) {
                    $yearlyPrice = Price::create([
                        'product' => $product->id,
                        'unit_amount' => $planData['price_yearly'],
                        'currency' => $planData['currency'],
                        'recurring' => [
                            'interval' => 'year'
                        ],
                        'metadata' => [
                            'plan_type' => $planType,
                            'billing_interval' => 'year'
                        ]
                    ]);
                    $planResults['price_id_yearly'] = $yearlyPrice->id;
                }
            }

            $results[$planType] = $planResults;
        }

        return $results;
    }
}
