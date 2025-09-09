<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing projects with company_id from their owner
        \DB::statement("
            UPDATE taskit_projects 
            SET company_id = (
                SELECT company_id 
                FROM taskit_users 
                WHERE taskit_users.id = taskit_projects.owner_id
            )
            WHERE company_id IS NULL
        ");
        
        // Update existing todos with company_id from their user
        \DB::statement("
            UPDATE taskit_todos 
            SET company_id = (
                SELECT company_id 
                FROM taskit_users 
                WHERE taskit_users.id = taskit_todos.user_id
            )
            WHERE company_id IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set company_id to NULL for all projects and todos
        \DB::statement("UPDATE taskit_projects SET company_id = NULL");
        \DB::statement("UPDATE taskit_todos SET company_id = NULL");
    }
};
