<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class UpdateCompanySubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'company:update-subscription {company_id} {action} {--plan=} {--minutes-ago=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update company subscription for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyId = $this->argument('company_id');
        $action = $this->argument('action');
        $plan = $this->option('plan');
        $minutesAgo = $this->option('minutes-ago');

        $company = Company::find($companyId);
        
        if (!$company) {
            $this->error("Company {$companyId} not found");
            return 1;
        }

        $this->info("Company: {$company->name} (ID: {$company->id})");
        $this->line("Current Plan: {$company->subscription_type}");
        $this->line("Scheduled Plan: " . ($company->scheduled_subscription_type ?? 'None'));
        $this->line("Scheduled Date: " . ($company->scheduled_change_date ?? 'None'));
        $this->line('');

        switch ($action) {
            case 'schedule-change':
                if (!$plan) {
                    $this->error('--plan option is required for schedule-change action');
                    return 1;
                }
                
                $scheduledDate = now()->subMinutes($minutesAgo);
                $company->update([
                    'scheduled_subscription_type' => $plan,
                    'scheduled_change_date' => $scheduledDate,
                ]);
                
                $this->info("✅ Scheduled change set:");
                $this->line("  From: {$company->subscription_type}");
                $this->line("  To: {$plan}");
                $this->line("  Date: {$scheduledDate->format('Y-m-d H:i:s')} ({$minutesAgo} minutes ago)");
                break;

            case 'apply-change':
                if (!$company->scheduled_subscription_type || !$company->scheduled_change_date) {
                    $this->error('No scheduled change found to apply');
                    return 1;
                }

                $oldPlan = $company->subscription_type;
                $newPlan = $company->scheduled_subscription_type;
                
                $success = $company->applyScheduledChange();
                
                if ($success) {
                    $this->info("✅ Applied scheduled change: {$oldPlan} → {$newPlan}");
                } else {
                    $this->error("❌ Failed to apply scheduled change");
                    return 1;
                }
                break;

            case 'direct-update':
                if (!$plan) {
                    $this->error('--plan option is required for direct-update action');
                    return 1;
                }
                
                $oldPlan = $company->subscription_type;
                $company->update([
                    'subscription_type' => $plan,
                    'scheduled_subscription_type' => null,
                    'scheduled_change_date' => null,
                ]);
                
                $this->info("✅ Direct update: {$oldPlan} → {$plan}");
                break;

            default:
                $this->error("Unknown action: {$action}");
                $this->line("Available actions: schedule-change, apply-change, direct-update");
                return 1;
        }

        // Show final status
        $company = $company->fresh();
        $this->line('');
        $this->info("Final Status:");
        $this->line("Current Plan: {$company->subscription_type}");
        $this->line("Scheduled Plan: " . ($company->scheduled_subscription_type ?? 'None'));
        $this->line("Scheduled Date: " . ($company->scheduled_change_date ?? 'None'));

        return 0;
    }
}