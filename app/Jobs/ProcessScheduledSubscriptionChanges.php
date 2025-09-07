<?php

namespace App\Jobs;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessScheduledSubscriptionChanges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting scheduled subscription changes processing job');

        // Find companies with overdue scheduled changes
        $companies = Company::whereNotNull('scheduled_subscription_type')
            ->whereNotNull('scheduled_change_date')
            ->where('scheduled_change_date', '<=', now())
            ->get();

        if ($companies->isEmpty()) {
            Log::info('No overdue scheduled subscription changes found');
            return;
        }

        Log::info('Found overdue scheduled changes', ['count' => $companies->count()]);

        $processed = 0;
        $failed = 0;

        foreach ($companies as $company) {
            try {
                $oldPlan = $company->subscription_type;
                $newPlan = $company->scheduled_subscription_type;
                $scheduledDate = $company->scheduled_change_date;

                Log::info('Processing scheduled change', [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'from_plan' => $oldPlan,
                    'to_plan' => $newPlan,
                    'scheduled_date' => $scheduledDate->toISOString(),
                    'overdue_by' => $scheduledDate->diffForHumans()
                ]);

                $success = $company->applyScheduledChange();

                if ($success) {
                    Log::info('Successfully applied scheduled subscription change', [
                        'company_id' => $company->id,
                        'from_plan' => $oldPlan,
                        'to_plan' => $newPlan
                    ]);
                    $processed++;

                    // Dispatch project reload event if plan affects project limits
                    if (in_array($newPlan, ['FREE', 'MIDI'])) {
                        Log::info('Plan change may affect project limits, triggering reload', [
                            'company_id' => $company->id,
                            'new_plan' => $newPlan
                        ]);
                    }
                } else {
                    Log::error('Failed to apply scheduled subscription change', [
                        'company_id' => $company->id,
                        'scheduled_date' => $scheduledDate->toISOString()
                    ]);
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::error('Exception while processing scheduled subscription change', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $failed++;
            }
        }

        Log::info('Completed scheduled subscription changes processing', [
            'total_found' => $companies->count(),
            'processed' => $processed,
            'failed' => $failed
        ]);
    }
}