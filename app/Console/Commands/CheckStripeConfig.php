<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckStripeConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:check-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Stripe configuration for TaskIT';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking Stripe configuration...');
        $this->line('');

        // Check basic Stripe keys
        $publicKey = config('stripe.public_key');
        $secretKey = config('stripe.secret_key');
        $webhookSecret = config('stripe.webhook_secret');

        $this->checkConfig('STRIPE_PUBLIC_KEY', $publicKey, 'pk_');
        $this->checkConfig('STRIPE_SECRET_KEY', $secretKey, 'sk_');
        $this->checkConfig('STRIPE_WEBHOOK_SECRET', $webhookSecret, 'whsec_', false);

        $this->line('');
        $this->info('Checking plan price IDs...');

        $plans = config('stripe.plans');
        $allConfigured = true;

        foreach ($plans as $planType => $planData) {
            if ($planType === 'FREE') {
                $this->line("✅ {$planType}: No Stripe integration needed");
                continue;
            }

            $priceId = $planData['stripe_price_id'] ?? null;
            if ($priceId) {
                $this->line("✅ {$planType}: {$priceId}");
            } else {
                $this->line("❌ {$planType}: STRIPE_{$planType}_PRICE_ID not configured");
                $allConfigured = false;
            }
        }

        $this->line('');

        if ($publicKey && $secretKey && $allConfigured) {
            $this->info('🎉 Stripe is fully configured!');
        } else {
            $this->warn('⚠️  Stripe configuration incomplete.');
            $this->line('');
            $this->info('To complete setup:');
            
            if (!$publicKey || !$secretKey) {
                $this->line('1. Add your Stripe keys to .env file');
            }
            
            if (!$allConfigured) {
                $this->line('2. Run: php artisan stripe:setup-products');
                $this->line('3. Add the generated price IDs to your .env file');
            }
        }

        return 0;
    }

    private function checkConfig(string $name, ?string $value, string $prefix, bool $required = true): void
    {
        if ($value && str_starts_with($value, $prefix)) {
            $maskedValue = $prefix . str_repeat('*', strlen($value) - strlen($prefix) - 4) . substr($value, -4);
            $this->line("✅ {$name}: {$maskedValue}");
        } elseif ($value) {
            $this->line("❌ {$name}: Invalid format (should start with '{$prefix}')");
        } elseif ($required) {
            $this->line("❌ {$name}: Not configured");
        } else {
            $this->line("⚠️  {$name}: Not configured (optional)");
        }
    }
}