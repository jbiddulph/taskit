<?php

namespace App\Console\Commands;

use App\Services\AutomationRunnerService;
use Illuminate\Console\Command;

class RunPlatformAutomations extends Command
{
    protected $signature = 'platform:run-automations {--date= : Optional YYYY-MM-DD to evaluate date_reached triggers}';

    protected $description = 'Run ZapTask platform date_reached automations';

    public function handle(AutomationRunnerService $runner): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))
            : null;

        $ran = $runner->runDateReachedAutomations($date);

        $this->info("Ran {$ran} automation(s).");

        return self::SUCCESS;
    }
}
