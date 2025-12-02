<?php

namespace App\Console\Commands;

use App\Services\StripeService;
use Illuminate\Console\Command;

class SetupStripeProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:setup-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Stripe products and prices for TaskIT subscription plans';

    protected StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        parent::__construct();
        $this->stripeService = $stripeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up Stripe products and prices...');

        try {
            $results = $this->stripeService->createProducts();

            $this->info('✅ Successfully created Stripe products and prices!');
            $this->line('');
            $this->info('Add these environment variables to your .env file:');
            $this->line('');

            foreach ($results as $planType => $data) {
                $this->line("STRIPE_{$planType}_PRICE_ID={$data['price_id']}");
                if (isset($data['price_id_yearly'])) {
                    $this->line("STRIPE_{$planType}_PRICE_ID_YEARLY={$data['price_id_yearly']}");
                }
            }

            $this->line('');
            $this->info('Product details:');
            foreach ($results as $planType => $data) {
                $line = "• {$planType}: Product ID {$data['product_id']}, Monthly Price ID {$data['price_id']}";
                if (isset($data['price_id_yearly'])) {
                    $line .= ", Yearly Price ID {$data['price_id_yearly']}";
                }
                $this->line($line);
            }

            $this->line('');
            $this->warn('⚠️  Don\'t forget to:');
            $this->line('1. Update your .env file with the price IDs above');
            $this->line('2. Restart your application');
            $this->line('3. Set up webhook endpoint in Stripe dashboard:');
            $this->line('   ' . config('app.url') . '/stripe/webhook');

        } catch (\Exception $e) {
            $this->error('❌ Failed to create Stripe products: ' . $e->getMessage());
            $this->line('');
            $this->info('Make sure your Stripe keys are configured correctly in your .env file:');
            $this->line('STRIPE_PUBLIC_KEY=pk_test_...');
            $this->line('STRIPE_SECRET_KEY=sk_test_...');
            return 1;
        }

        return 0;
    }
}