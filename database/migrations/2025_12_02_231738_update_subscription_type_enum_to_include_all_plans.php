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
        // For MySQL/MariaDB: Need to modify enum to include all plan types
        // For SQLite: Enums are stored as strings, so we just need to ensure the column accepts the new values
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            // MySQL doesn't support modifying enum directly, so we need to:
            // 1. Change to string temporarily
            // 2. Change back to enum with all values
            Schema::table('taskit_companies', function (Blueprint $table) {
                $table->string('subscription_type', 50)->default('FREE')->change();
            });
            
            // Now change back to enum with all plan types
            DB::statement("ALTER TABLE taskit_companies MODIFY subscription_type ENUM('FREE', 'MIDI', 'MAXI', 'BUSINESS', 'LTD_SOLO', 'LTD_TEAM', 'LTD_AGENCY', 'LTD_BUSINESS') DEFAULT 'FREE'");
        } else {
            // For SQLite and PostgreSQL, enums are stored as strings
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
