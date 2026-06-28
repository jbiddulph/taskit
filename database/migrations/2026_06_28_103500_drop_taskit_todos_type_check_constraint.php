<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Laravel enum columns become CHECK constraints in Postgres; allow custom "Other" types.
        DB::statement("
            DO $$
            BEGIN
                IF EXISTS (
                    SELECT 1
                    FROM pg_constraint
                    WHERE conname = 'taskit_todos_type_check'
                ) THEN
                    ALTER TABLE taskit_todos DROP CONSTRAINT taskit_todos_type_check;
                END IF;
            END $$;
        ");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("
            ALTER TABLE taskit_todos
            ADD CONSTRAINT taskit_todos_type_check
            CHECK (type IS NULL OR type IN ('Bug', 'Feature', 'Task', 'Story', 'Epic'))
        ");
    }
};
