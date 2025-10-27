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
        // Enable RLS for taskit_notifications table
        \DB::statement("ALTER TABLE taskit_notifications ENABLE ROW LEVEL SECURITY");
        
        // Allow authenticated users to read their own notifications
        \DB::statement("
            CREATE POLICY \"Users can read own notifications\" ON taskit_notifications
            FOR SELECT
            USING (auth.uid()::text = user_id::text)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop RLS policies
        \DB::statement("DROP POLICY IF EXISTS \"Users can read own notifications\" ON taskit_notifications");
        \DB::statement("ALTER TABLE taskit_notifications DISABLE ROW LEVEL SECURITY");
    }
};
