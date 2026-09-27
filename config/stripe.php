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
                '1 user',
                '3 projects',
                '200 todos',
                '10 AI document reads / month',
                'Basic features',
                // Core FREE features
                'Bulk Add Tasks',
                'Multiple Update Tasks',
                'Activity Feed',
                'Save Filters',
                'Notifications',
                'Voice Add Tasks (10 second)',
                'Sub Tasks',
                'Task Images',
                'Calendar View',
                'Team @ mentions',
                'Team Chat',
            ]
        ],
        'MIDI' => [
            'name' => 'MIDI Plan',
            'price' => 600, // £6.00 in pence
            'price_yearly' => 6000, // £60.00 in pence (save £12)
            'currency' => 'gbp',
            'stripe_price_id' => env('STRIPE_MIDI_PRICE_ID'),
            'stripe_price_id_yearly' => env('STRIPE_MIDI_PRICE_ID_YEARLY'),
            'features' => [
                'Up to 5 members',
                'Up to 10 clients',
                'Up to 20 projects per client',
                'Unlimited todos',
                '100 AI document reads / month',
                // Core app features
                'Bulk Add Tasks',
                'Voice Add Tasks (10 second)',
                'Multiple Update Tasks',
                'Activity Feed',
                'Save Filters',
                'Notifications',
                'Sub Tasks',
                'Task Images',
                'Calendar View',
                'Client Project Filter',
                'Team @ mentions',
                'Team Chat',
                'Custom Logo',
                'Company Sub Domain',
                'Public Facing Dashboard',
                'Import/Export data',
                'Sites & Assets',
                'Property Compliance',
                'Document upload & AI scanning',
            ]
        ],
        'MAXI' => [
            'name' => 'MAXI Plan',
            'price' => 1200, // £12.00 in pence
            'price_yearly' => 12000, // £120.00 in pence (save £24)
            'currency' => 'gbp',
            'stripe_price_id' => env('STRIPE_MAXI_PRICE_ID'),
            'stripe_price_id_yearly' => env('STRIPE_MAXI_PRICE_ID_YEARLY'),
            'features' => [
                'Up to 20 members',
                'Up to 30 clients',
                'Up to 40 projects per client',
                'Unlimited todos',
                '500 AI document reads / month',
                // Core app features
                'Bulk Add Tasks',
                'Voice Add Tasks (10 second)',
                'Multiple Update Tasks',
                'Activity Feed',
                'Save Filters',
                'Notifications',
                'Sub Tasks',
                'Task Images',
                'Calendar View',
                'Client Project Filter',
                'Team @ mentions',
                'Team Chat',
                'Custom Logo',
                'Company Sub Domain',
                'Public Facing Dashboard',
                'Import/Export data',
                'Sites & Assets',
                'Property Compliance',
                'Document upload & AI scanning',
            ]
        ],
        'BUSINESS' => [
            'name' => 'BUSINESS Plan',
            'price' => 2900, // £29.00 in pence
            'currency' => 'gbp',
            'stripe_price_id' => env('STRIPE_BUSINESS_PRICE_ID'),
            'features' => [
                'Unlimited members',
                'Unlimited projects',
                '2000 AI document reads / month',
                'Admin controls',
                'SSO / audit logs',
                'Support SLAs',
                // Core app features
                'Bulk Add Tasks',
                'Multiple Update Tasks',
                'Activity Feed',
                'Save Filters',
                'Notifications',
                'Voice Add Tasks (10 second)',
                'Sub Tasks',
                'Task Images',
                'Calendar View',
                'Client Project Filter',
                'Team @ mentions',
                'Team Chat',
                'Custom Logo',
                'Company Sub Domain',
                'Public Facing Dashboard',
                'Import/Export data',
                'Sites & Assets',
                'Property Compliance',
                'Document upload & AI scanning',
            ]
        ],
        'LTD_SOLO' => [
            'name' => 'LTD Solo',
            'price' => 3900, // £39.00 in pence (one-time)
            'currency' => 'gbp',
            'stripe_price_id' => env('STRIPE_LTD_SOLO_PRICE_ID'),
            'is_lifetime' => true,
            'features' => [
                '1 user',
                '10 projects',
                '0 clients (solo workspace only)',
                'Unlimited todos',
                '100 AI document reads / month',
                'No team features',
                // Core solo features (workspace-level)
                'Bulk Add Tasks',
                'Multiple Update Tasks',
                'Activity Feed',
                'Save Filters',
                'Notifications',
                'Voice Add Tasks (10 second)',
                'Sub Tasks',
                'Task Images',
                'Calendar View',
            ]
        ],
        'LTD_TEAM' => [
            'name' => 'LTD Team',
            'price' => 7900, // £79.00 in pence (one-time)
            'currency' => 'gbp',
            'stripe_price_id' => env('STRIPE_LTD_TEAM_PRICE_ID'),
            'is_lifetime' => true,
            'features' => [
                'Up to 5 members',
                'Up to 10 clients',
                'Up to 20 projects per client',
                'Unlimited todos',
                '500 AI document reads / month',
                'All MIDI-level features',
                // Core app features
                'Bulk Add Tasks',
                'Multiple Update Tasks',
                'Activity Feed',
                'Save Filters',
                'Notifications',
                'Voice Add Tasks (10 second)',
                'Sub Tasks',
                'Task Images',
                'Calendar View',
                'Client Project Filter',
                'Team @ mentions',
                'Team Chat',
                'Custom Logo',
                'Company Sub Domain',
                'Public Facing Dashboard',
                'Import/Export data',
            ]
        ],
        'LTD_AGENCY' => [
            'name' => 'LTD Agency',
            'price' => 13900, // £139.00 in pence (one-time)
            'currency' => 'gbp',
            'stripe_price_id' => env('STRIPE_LTD_AGENCY_PRICE_ID'),
            'is_lifetime' => true,
            'features' => [
                'Up to 20 members',
                'Up to 30 clients',
                'Up to 40 projects per client',
                'Unlimited todos',
                '1000 AI document reads / month',
                'Most features (except enterprise-level)',
                // Core app features
                'Bulk Add Tasks',
                'Multiple Update Tasks',
                'Activity Feed',
                'Save Filters',
                'Notifications',
                'Voice Add Tasks (10 second)',
                'Sub Tasks',
                'Task Images',
                'Calendar View',
                'Client Project Filter',
                'Team @ mentions',
                'Team Chat',
                'Custom Logo',
                'Company Sub Domain',
                'Public Facing Dashboard',
                'Import/Export data',
            ]
        ],
        'LTD_BUSINESS' => [
            'name' => 'LTD Business',
            'price' => 19900, // £199.00 in pence (one-time)
            'currency' => 'gbp',
            'stripe_price_id' => env('STRIPE_LTD_BUSINESS_PRICE_ID'),
            'is_lifetime' => true,
            'features' => [
                'Up to 50 members',
                'Up to 50 clients',
                'Up to 100 projects per client',
                '2000 AI document reads / month',
                'All current features',
                'Priority support',
                // Core app features
                'Bulk Add Tasks',
                'Multiple Update Tasks',
                'Activity Feed',
                'Save Filters',
                'Notifications',
                'Voice Add Tasks (10 second)',
                'Sub Tasks',
                'Task Images',
                'Calendar View',
                'Client Project Filter',
                'Team @ mentions',
                'Team Chat',
                'Custom Logo',
                'Company Sub Domain',
                'Public Facing Dashboard',
                'Import/Export data',
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Property Compliance AI — B2B document/AI allowance tiers
    |--------------------------------------------------------------------------
    |
    | Positioning for specialised front ends (Property / Fleet / HR). ZapTask
    | remains the workflow engine; these tiers describe document AI capacity.
    |
    */
    'document_ai_tiers' => [
        [
            'key' => 'free',
            'label' => 'Free',
            'price_label' => '£0',
            'plan' => 'FREE',
            'reads' => 10,
            'blurb' => 'Try portfolio ask and a handful of certificate scans.',
        ],
        [
            'key' => 'starter',
            'label' => 'Starter',
            'price_label' => '£29/mo',
            'plan' => 'BUSINESS',
            'reads' => 2000,
            'blurb' => 'Portfolio scanning for landlords and small agencies.',
        ],
        [
            'key' => 'growth',
            'label' => 'Growth',
            'price_label' => '£79',
            'plan' => 'LTD_TEAM',
            'reads' => 500,
            'blurb' => 'Team workspaces with higher monthly AI reads.',
        ],
        [
            'key' => 'agency',
            'label' => 'Agency',
            'price_label' => '£199+',
            'plan' => 'LTD_BUSINESS',
            'reads' => 2000,
            'blurb' => 'Larger portfolios, priority support, full feature set.',
        ],
    ],
];
