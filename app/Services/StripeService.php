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

        return Session::create([
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
            'metadata' => [
                'company_id' => $company->id,
                'plan_type' => $planType,
            ],
            'subscription_data' => [
                'metadata' => [
                    'company_id' => $company->id,
                    'plan_type' => $planType,
                ]
            ]
        ]);
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
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId): Subscription
    {
        return Subscription::retrieve($subscriptionId)->cancel();
    }

    /**
     * Update subscription to new plan
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
     * Get subscription details
     */
    public function getSubscription(string $subscriptionId): Subscription
    {
        return Subscription::retrieve($subscriptionId);
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
