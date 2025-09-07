<?php

namespace App\Console\Commands;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessScheduledSubscriptionChanges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:process-scheduled-changes {--dry-run : Show what would be changed without making changes} {--company-id= : Process specific company ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled subscription changes that are due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $companyId = $this->option('company-id');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->line('');
        }

        // Build query for companies with scheduled changes
        $query = Company::whereNotNull('scheduled_subscription_type')
            ->whereNotNull('scheduled_change_date');
            
        if ($companyId) {
            $query->where('id', $companyId);
        }
        
        $companies = $query->get();

        if ($companies->isEmpty()) {
            $this->info('✅ No companies found with scheduled subscription changes');
            return;
        }

        $this->info("📋 Found {$companies->count()} companies with scheduled changes");
        $this->line('');

        $processed = 0;
        $pending = 0;

        foreach ($companies as $company) {
            $scheduledDate = $company->scheduled_change_date;
            $isOverdue = $scheduledDate->isPast();
            $timeLeft = $scheduledDate->diffForHumans();
            
            $this->line("Company: {$company->name} (ID: {$company->id})");
            $this->line("  Current Plan: {$company->subscription_type}");
            $this->line("  Scheduled Plan: {$company->scheduled_subscription_type}");
            $this->line("  Scheduled Date: {$scheduledDate->format('Y-m-d H:i:s')} ({$timeLeft})");
            
            if ($isOverdue) {
                $this->warn("  ⏰ OVERDUE - Ready to process");
                
                if (!$isDryRun) {
                    $success = $company->applyScheduledChange();
                    if ($success) {
                        $this->info("  ✅ Applied scheduled change to {$company->scheduled_subscription_type}");
                        $processed++;
                    } else {
                        $this->error("  ❌ Failed to apply scheduled change");
                    }
                } else {
                    $this->info("  📝 Would apply scheduled change to {$company->scheduled_subscription_type}");
                    $processed++;
                }
            } else {
                $this->info("  ⏳ Pending - {$timeLeft}");
                $pending++;
            }
            
            $this->line('');
        }

        $this->line('📊 Summary:');
        $this->info("  ✅ Processed (overdue): {$processed}");
        $this->info("  ⏳ Pending (future): {$pending}");
        
        if ($isDryRun && $processed > 0) {
            $this->line('');
            $this->info('🚀 To apply overdue changes, run: php artisan subscription:process-scheduled-changes');
        }
        
        if ($processed > 0 && !$isDryRun) {
            $this->line('');
            $this->info('🎉 Scheduled changes processed!');
        }
    }
}