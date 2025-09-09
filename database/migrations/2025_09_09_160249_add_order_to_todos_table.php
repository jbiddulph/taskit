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
        Schema::table('taskit_todos', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('status');
            $table->index(['project_id', 'status', 'order']);
        });

        // Initialize order for existing todos based on creation date
        DB::statement("
            UPDATE taskit_todos 
            SET \"order\" = ranked.row_number
            FROM (
                SELECT id, ROW_NUMBER() OVER (
                    PARTITION BY project_id, status 
                    ORDER BY created_at ASC
                ) as row_number
                FROM taskit_todos
            ) ranked 
            WHERE ranked.id = taskit_todos.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taskit_todos', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'status', 'order']);
            $table->dropColumn('order');
        });
    }
};