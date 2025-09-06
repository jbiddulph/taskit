<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\StripeService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class BackfillSubscriptionEndDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:backfill-end-dates {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill subscription_ends_at dates for companies with Stripe subscriptions';

    /**
     * Create a new command instance.
     */
    public function __construct(
        protected StripeService $stripeService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->line('');
        }

        // Find companies with Stripe subscriptions but missing subscription_ends_at
        $companies = Company::whereNotNull('stripe_subscription_id')
            ->whereNull('subscription_ends_at')
            ->get();

        if ($companies->isEmpty()) {
            $this->info('✅ No companies found that need subscription_ends_at backfill');
            return;
        }

        $this->info("📋 Found {$companies->count()} companies with missing subscription_ends_at dates");
        $this->line('');

        $updated = 0;
        $failed = 0;

        foreach ($companies as $company) {
            $this->line("Processing company: {$company->name} (ID: {$company->id})");
            
            try {
                // Retrieve subscription from Stripe
                $subscription = $this->stripeService->retrieveSubscription($company->stripe_subscription_id);
                
                $periodEnd = null;
                
                if ($subscription->current_period_end) {
                    $periodEnd = Carbon::createFromTimestamp($subscription->current_period_end);
                    $this->info("  📅 Found Stripe period end: {$periodEnd->format('Y-m-d H:i:s')}");
                } else {
                    // Estimate period end as next month from now
                    $periodEnd = now()->addMonth()->startOfDay();
                    $this->warn("  ⚠️  No period end in Stripe, estimating: {$periodEnd->format('Y-m-d H:i:s')}");
                }
                
                if (!$isDryRun) {
                    $company->update(['subscription_ends_at' => $periodEnd]);
                    $this->info("  ✅ Updated subscription_ends_at");
                } else {
                    $this->info("  📝 Would update subscription_ends_at to: {$periodEnd->format('Y-m-d H:i:s')}");
                }
                
                $updated++;
                
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to process company {$company->id}: {$e->getMessage()}");
                $failed++;
            }
            
            $this->line('');
        }

        $this->line('📊 Summary:');
        $this->info("  ✅ Successfully processed: {$updated}");
        if ($failed > 0) {
            $this->error("  ❌ Failed: {$failed}");
        }
        
        if ($isDryRun) {
            $this->line('');
            $this->info('🚀 To apply these changes, run: php artisan subscription:backfill-end-dates');
        } else {
            $this->line('');
            $this->info('🎉 Backfill completed!');
        }
    }
}