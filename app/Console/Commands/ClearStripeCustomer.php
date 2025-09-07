<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class ClearStripeCustomer extends Command
{
    protected $signature = 'stripe:clear-customer {company_id}';
    protected $description = 'Clear Stripe customer data for a company';

    public function handle()
    {
        $companyId = $this->argument('company_id');
        $company = Company::find($companyId);
        
        if (!$company) {
            $this->error("Company with ID {$companyId} not found");
            return 1;
        }
        
        $this->info("Current Stripe data for company {$company->name}:");
        $this->info("Customer ID: " . ($company->stripe_customer_id ?? 'NULL'));
        $this->info("Subscription ID: " . ($company->stripe_subscription_id ?? 'NULL'));
        $this->info("Subscription Type: " . $company->subscription_type);
        
        if ($this->confirm('Clear all Stripe customer data?')) {
            $company->update([
                'stripe_customer_id' => null,
                'stripe_subscription_id' => null,
                'subscription_status' => 'active',
                'subscription_type' => 'FREE',
                'subscription_ends_at' => null,
                'scheduled_subscription_type' => null,
                'scheduled_change_date' => null,
            ]);
            
            $this->info('Stripe customer data cleared successfully!');
            $this->info('Company is now on FREE plan with no Stripe associations.');
        }
        
        return 0;
    }
}
