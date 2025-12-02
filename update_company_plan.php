<?php

/**
 * Helper script to update company subscription plan
 * Usage: php update_company_plan.php <company_id> <plan_type>
 * Example: php update_company_plan.php 1 LTD_AGENCY
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$companyId = $argv[1] ?? null;
$planType = $argv[2] ?? null;

if (!$companyId || !$planType) {
    echo "Usage: php update_company_plan.php <company_id> <plan_type>\n";
    echo "Example: php update_company_plan.php 1 LTD_AGENCY\n";
    echo "\nValid plan types: FREE, MIDI, MAXI, BUSINESS, LTD_SOLO, LTD_TEAM, LTD_AGENCY, LTD_BUSINESS\n";
    exit(1);
}

$validPlans = ['FREE', 'MIDI', 'MAXI', 'BUSINESS', 'LTD_SOLO', 'LTD_TEAM', 'LTD_AGENCY', 'LTD_BUSINESS'];

if (!in_array($planType, $validPlans)) {
    echo "Error: Invalid plan type. Must be one of: " . implode(', ', $validPlans) . "\n";
    exit(1);
}

$company = \App\Models\Company::find($companyId);

if (!$company) {
    echo "Error: Company with ID {$companyId} not found.\n";
    echo "\nAvailable companies:\n";
    $companies = \App\Models\Company::all(['id', 'name', 'code', 'subscription_type']);
    foreach ($companies as $c) {
        echo "  ID: {$c->id}, Name: {$c->name}, Current Plan: {$c->subscription_type}\n";
    }
    exit(1);
}

echo "Updating company:\n";
echo "  ID: {$company->id}\n";
echo "  Name: {$company->name}\n";
echo "  Current Plan: {$company->subscription_type}\n";
echo "  New Plan: {$planType}\n\n";

try {
    $company->subscription_type = $planType;
    $company->subscription_status = 'active';
    
    // For LTD plans, clear subscription-related fields
    if (str_starts_with($planType, 'LTD_')) {
        $company->stripe_subscription_id = null;
        $company->subscription_ends_at = null;
    }
    
    // Clear any scheduled changes
    $company->scheduled_subscription_type = null;
    $company->scheduled_change_date = null;
    
    $company->save();
    
    echo "✅ Successfully updated company to {$planType}!\n\n";
    echo "New limits:\n";
    echo "  Members: " . ($company->getMemberLimit() === PHP_INT_MAX ? 'Unlimited' : $company->getMemberLimit()) . "\n";
    echo "  Projects: " . ($company->getProjectLimit() === PHP_INT_MAX ? 'Unlimited' : $company->getProjectLimit()) . "\n";
    echo "  Todos: " . ($company->getTodoLimit() === PHP_INT_MAX ? 'Unlimited' : $company->getTodoLimit()) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Error updating company: " . $e->getMessage() . "\n";
    exit(1);
}

