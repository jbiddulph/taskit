<?php

namespace App\Console\Commands;

use App\Services\AutomationRunnerService;
use Illuminate\Console\Command;

class RunPlatformAutomations extends Command
{
    protected $signature = 'platform:run-automations {--date= : Optional YYYY-MM-DD to evaluate scheduled triggers}';

    protected $description = 'Run ZapTask platform scheduled automations (date_reached, task_overdue, compliance_expiring)';

    public function handle(AutomationRunnerService $runner): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))
            : null;

        $ran = $runner->runScheduledAutomations($date);

        $this->info("Ran {$ran} automation action(s).");

        return self::SUCCESS;
    }
}
