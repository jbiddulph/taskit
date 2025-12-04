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
        $driver = DB::getDriverName();
        
        if ($driver === 'pgsql') {
            // PostgreSQL: Check if there's a check constraint and update it
            // First, drop the existing check constraint if it exists
            DB::statement("
                DO \$\$ 
                BEGIN
                    -- Drop existing check constraint if it exists
                    IF EXISTS (
                        SELECT 1 FROM pg_constraint 
                        WHERE conrelid = 'taskit_companies'::regclass 
                        AND conname LIKE '%subscription_type%'
                        AND contype = 'c'
                    ) THEN
                        ALTER TABLE taskit_companies DROP CONSTRAINT taskit_companies_subscription_type_check;
                    END IF;
                END
                \$\$;
            ");
            
            // Ensure column is wide enough for new values
            Schema::table('taskit_companies', function (Blueprint $table) {
                $table->string('subscription_type', 50)->default('FREE')->change();
            });
            
            // Add new check constraint with all plan types (including LTD_JB)
            DB::statement("
                ALTER TABLE taskit_companies 
                ADD CONSTRAINT taskit_companies_subscription_type_check 
                CHECK (subscription_type IN ('FREE', 'MIDI', 'MAXI', 'BUSINESS', 'LTD_SOLO', 'LTD_TEAM', 'LTD_AGENCY', 'LTD_BUSINESS', 'LTD_JB'))
            ");
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            // MySQL doesn't support modifying enum directly, so we need to:
            // 1. Change to string temporarily
            // 2. Change back to enum with all values
            Schema::table('taskit_companies', function (Blueprint $table) {
                $table->string('subscription_type', 50)->default('FREE')->change();
            });
            
            // Now change back to enum with all plan types (including LTD_JB)
            DB::statement("ALTER TABLE taskit_companies MODIFY subscription_type ENUM('FREE', 'MIDI', 'MAXI', 'BUSINESS', 'LTD_SOLO', 'LTD_TEAM', 'LTD_AGENCY', 'LTD_BUSINESS', 'LTD_JB') DEFAULT 'FREE'");
        } else {
            // For SQLite, enums are stored as strings
            // Just ensure the column can handle longer values
            Schema::table('taskit_companies', function (Blueprint $table) {
                $table->string('subscription_type', 50)->default('FREE')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Revert to original enum values
            DB::statement("ALTER TABLE taskit_companies MODIFY subscription_type ENUM('FREE', 'MIDI', 'MAXI') DEFAULT 'FREE'");
            
            // Update any non-standard values back to FREE
            DB::table('taskit_companies')
                ->whereNotIn('subscription_type', ['FREE', 'MIDI', 'MAXI'])
                ->update(['subscription_type' => 'FREE']);
        } else {
            // For SQLite/PostgreSQL, just change back to shorter string
            Schema::table('taskit_companies', function (Blueprint $table) {
                $table->string('subscription_type', 10)->default('FREE')->change();
            });
        }
    }
};
