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
            // PostgreSQL: drop and recreate the check constraint
            DB::statement("
                DO \$\$
                BEGIN
                    IF EXISTS (
                        SELECT 1 FROM pg_constraint
                        WHERE conrelid = 'taskit_companies'::regclass
                        AND conname = 'taskit_companies_subscription_type_check'
                        AND contype = 'c'
                    ) THEN
                        ALTER TABLE taskit_companies DROP CONSTRAINT taskit_companies_subscription_type_check;
                    END IF;
                END
                \$\$;
            ");

            // Ensure column is wide enough (safety)
            Schema::table('taskit_companies', function (Blueprint $table) {
                $table->string('subscription_type', 50)->default('FREE')->change();
            });

            DB::statement("
                ALTER TABLE taskit_companies
                ADD CONSTRAINT taskit_companies_subscription_type_check
                CHECK (subscription_type IN ('FREE', 'MIDI', 'MAXI', 'BUSINESS', 'LTD_SOLO', 'LTD_TEAM', 'LTD_AGENCY', 'LTD_BUSINESS'))
            ");
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            // MySQL / MariaDB: update enum definition
            Schema::table('taskit_companies', function (Blueprint $table) {
                $table->string('subscription_type', 50)->default('FREE')->change();
            });

            DB::statement("
                ALTER TABLE taskit_companies
                MODIFY subscription_type ENUM('FREE', 'MIDI', 'MAXI', 'BUSINESS', 'LTD_SOLO', 'LTD_TEAM', 'LTD_AGENCY', 'LTD_BUSINESS') DEFAULT 'FREE'
            ");
        } else {
            // SQLite / others: just ensure string width (no strict enum/check)
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

        if ($driver === 'pgsql') {
            // Best-effort: revert to constraint
            DB::statement("
                DO \$\$
                BEGIN
                    IF EXISTS (
                        SELECT 1 FROM pg_constraint
                        WHERE conrelid = 'taskit_companies'::regclass
                        AND conname = 'taskit_companies_subscription_type_check'
                        AND contype = 'c'
                    ) THEN
                        ALTER TABLE taskit_companies DROP CONSTRAINT taskit_companies_subscription_type_check;
                    END IF;
                END
                \$\$;
            ");

            DB::statement("
                ALTER TABLE taskit_companies
                ADD CONSTRAINT taskit_companies_subscription_type_check
                CHECK (subscription_type IN ('FREE', 'MIDI', 'MAXI', 'BUSINESS', 'LTD_SOLO', 'LTD_TEAM', 'LTD_AGENCY', 'LTD_BUSINESS'))
            ");
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("
                ALTER TABLE taskit_companies
                MODIFY subscription_type ENUM('FREE', 'MIDI', 'MAXI', 'BUSINESS', 'LTD_SOLO', 'LTD_TEAM', 'LTD_AGENCY', 'LTD_BUSINESS') DEFAULT 'FREE'
            ");
        } else {
            Schema::table('taskit_companies', function (Blueprint $table) {
                $table->string('subscription_type', 50)->default('FREE')->change();
            });
        }
    }
};


