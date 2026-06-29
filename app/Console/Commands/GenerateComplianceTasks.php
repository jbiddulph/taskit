<?php

namespace App\Console\Commands;

use App\Services\ComplianceTaskGeneratorService;
use Illuminate\Console\Command;

class GenerateComplianceTasks extends Command
{
    protected $signature = 'compliance:generate-upcoming-tasks';

    protected $description = 'Create reminder tasks for compliance requirements approaching their due dates';

    public function handle(ComplianceTaskGeneratorService $generator): int
    {
        $created = $generator->generateForAllCompanies();

        $this->info("Compliance tasks created: {$created}");

        return self::SUCCESS;
    }
}
