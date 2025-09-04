<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing check constraint
        DB::statement('ALTER TABLE taskit_todos DROP CONSTRAINT IF EXISTS taskit_todos_status_check');
        
        // Add the new check constraint with qa-testing status
        DB::statement('ALTER TABLE taskit_todos ADD CONSTRAINT taskit_todos_status_check CHECK (status IN (\'todo\', \'in-progress\', \'qa-testing\', \'done\'))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new check constraint
        DB::statement('ALTER TABLE taskit_todos DROP CONSTRAINT IF EXISTS taskit_todos_status_check');
        
        // Restore the original check constraint
        DB::statement('ALTER TABLE taskit_todos ADD CONSTRAINT taskit_todos_status_check CHECK (status IN (\'todo\', \'in-progress\', \'done\'))');
    }
};
