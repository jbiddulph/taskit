<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Stripe payment processing.
    |
    */

    'public_key' => env('STRIPE_PUBLIC_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Define the subscription plans and their Stripe price IDs.
    | These will be created in Stripe dashboard.
    |
    */
    'plans' => [
        'FREE' => [
            'name' => 'Free Plan',
            'price' => 0,
            'stripe_price_id' => null, // No Stripe subscription needed
            'features' => [
                'Individuals - unlimited projects & todos',
                'Small companies - up to 5 members',
                'Up to 10 projects',
                'Unlimited todos'
            ]
        ],
        'MIDI' => [
            'name' => 'MIDI Plan',
            'price' => 600, // £6.00 in pence
            'currency' => 'gbp',
            'stripe_price_id' => env('STRIPE_MIDI_PRICE_ID'),
            'features' => [
                'Small-medium companies - up to 10 members',
                'Up to 20 projects',
                'Unlimited todos',
                'Custom logo upload'
            ]
        ],
        'MAXI' => [
            'name' => 'MAXI Plan',
            'price' => 900, // £9.00 in pence
            'currency' => 'gbp',
            'stripe_price_id' => env('STRIPE_MAXI_PRICE_ID'),
            'features' => [
                'Medium-large companies - unlimited members',
                'Unlimited projects',
                'Unlimited todos',
                'Custom logo upload',
                'Custom color themes'
            ]
        ]
    ]
];
