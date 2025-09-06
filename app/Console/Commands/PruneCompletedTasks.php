<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Todo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneCompletedTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:prune-completed {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune completed tasks for companies that have enabled auto-pruning';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('Starting completed tasks pruning process...');
        
        // Get companies that have pruning enabled
        $companies = Company::where('prune_completed_tasks', true)->get();
        
        if ($companies->isEmpty()) {
            $this->info('No companies have auto-pruning enabled.');
            return 0;
        }
        
        $this->info("Found {$companies->count()} companies with auto-pruning enabled.");
        
        $totalDeleted = 0;
        
        foreach ($companies as $company) {
            $this->info("Processing company: {$company->name} ({$company->code})");
            
            // Get completed todos for this company through projects
            $completedTodos = Todo::whereHas('project', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->where('status', 'done')
            ->get();
            
            if ($completedTodos->isEmpty()) {
                $this->line("  No completed tasks found for {$company->name}");
                continue;
            }
            
            $todoCount = $completedTodos->count();
            
            if ($isDryRun) {
                $this->line("  [DRY RUN] Would delete {$todoCount} completed tasks for {$company->name}");
                $totalDeleted += $todoCount;
                continue;
            }
            
            // Start transaction for safe deletion
            DB::beginTransaction();
            
            try {
                // Delete todos (comments will be cascade deleted if foreign key is set up properly)
                foreach ($completedTodos as $todo) {
                    // Delete comments first to be explicit
                    $todo->comments()->delete();
                    $todo->delete();
                }
                
                DB::commit();
                
                $this->line("  ✓ Deleted {$todoCount} completed tasks and their comments for {$company->name}");
                $totalDeleted += $todoCount;
                
            } catch (\Exception $e) {
                DB::rollback();
                $this->error("  ✗ Failed to delete tasks for {$company->name}: " . $e->getMessage());
            }
        }
        
        if ($isDryRun) {
            $this->info("DRY RUN COMPLETE: Would delete {$totalDeleted} completed tasks total.");
        } else {
            $this->info("PRUNING COMPLETE: Deleted {$totalDeleted} completed tasks total.");
        }
        
        return 0;
    }
}
